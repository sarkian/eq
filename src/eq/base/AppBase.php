<?php

namespace eq\base;

use eq\console\ConsoleApp;
use eq\db\ConnectionBase;
use eq\db\Pool;
use eq\helpers\Str;
use eq\helpers\Arr;
use eq\helpers\FileSystem;
use eq\modules\dbconfig\DbconfigModule;
use eq\task\TaskApp;
use eq\web\WebApp;
use eq\web\WidgetBase;
use Exception;
use Glip_Binary;
use Glip_Git;

/**
 * @property string type
 * @property ModuleBase[] available_modules
 * @property ModuleBase[] enabled_modules
 * @property ModuleBase[] modules_by_class
 * @property ModuleBase[] modules_by_name
 * @property string classname
 * @property string classbasename
 * @property string app_namespace
 * @property ExceptionBase exception
 * @property int time
 * @property string locale
 * @property Cache cache
 * @property Pool db
 * @method static WidgetBase widget(string $name)
 * @method static string t($token)
 * @method static string k($token)
 * @method ConnectionBase db(string $name)
 * @property DbconfigModule dbconfig
 */
abstract class AppBase extends ModuleAbstract
{

    use TObject {
        __get as protected TObject_get;
        __set as protected TObject_set;
    }
    use TEvent;
    use TAlias;

    protected static $_app = null;
    protected static $static_methods = [];
    protected static $default_static_methods = [];

    protected $_config;
    protected $_original_config;
//    protected $_changed_config = [];
    protected $_app_namespace;
    protected $_components = [];
    protected $_exception;

    protected $system_components = [];
    protected $registered_components = [];
    protected $default_components = [];
    protected $config_permissions = [];

    protected $modules_by_name = [];
    protected $modules_by_class = [];
    protected $_available_modules = null;

    protected $locale = "en_US";

    abstract public function run();

    abstract public function processFatalError(array $err);

    abstract public function processException(ExceptionBase $e);

    abstract public function processUncaughtException(Exception $e);

    public function __construct($config)
    {
        foreach($this->directories() as $path => $mode)
            FileSystem::mkdir($path, $mode);
        $this->bind("exception", [$this, "__onException"]);
        self::$_app = $this;
        $this->processConfig($config);
        self::setAlias("@appsrc", APPROOT."/src/".$this->app_namespace);
        foreach($this->config("system.src_dirs", []) as $dir)
            Loader::addDir(realpath(self::getAlias($dir)));
        set_error_handler(['\eq\base\ErrorHandler', 'onError']);
        set_exception_handler(['\eq\base\ErrorHandler', 'onException']);
        register_shutdown_function(['\eq\base\ErrorHandler', 'onShutdown']);
        $this->default_components = $this->defaultComponents();
        static::$default_static_methods = static::defaultStaticMethods();
        static::$static_methods = static::systemStaticMethods();
        $this->system_components = $this->systemComponents();
        foreach($this->system_components as $name => $component) {
            if(Arr::getItem($component, "preload", false))
                $this->loadComponent($name);
        }
        $this->config_permissions = $this->configPermissions();
        try {
            $this->loadModules();
            $this->trigger("ready");
        }
        catch(ExceptionBase $e) {
            $this->processException($e);
        }
        catch(Exception $ue) {
            $this->processUncaughtException($ue);
        }
    }

    /**
     * @param string $name
     * @param bool $nothrow
     * @return ModuleBase|bool
     * @throws ModuleException
     */
    public function module($name, $nothrow = false)
    {
        if(isset($this->modules_by_name[$name]))
            return $this->modules_by_name[$name];
        elseif(isset($this->available_modules[$name])) {
            $module = $this->available_modules[$name];
            $this->modules_by_name[$name] = $module;
            $this->modules_by_class[get_class($module)] = $module;
            return $module;
        }
        elseif($nothrow)
            return false;
        else
            throw new ModuleException("Module not found: $name");
    }

    public function isModuleAvaliable($name)
    {
        $module = $this->module($name, true);
        return $module ? true : false;
    }

    public function isModuleEnabled($name)
    {
        $module = $this->module($name, false);
        return $module ? $module->isEnabled() : false;
    }

    public function __onException($e)
    {
        $this->_exception = $e;
    }

    public function __get($name)
    {
        if($this->getterExists($name))
            return $this->TObject_get($name);
        elseif(!isset($this->_components[$name])) {
            $this->loadComponent($name);
        }
        return $this->_components[$name];
    }

    public function __set($name, $value)
    {
        if($this->setterExists($name))
            $this->TObject_set($name, $value);
        else
            throw new InvalidCallException(
                "Setting application property: $name");
    }

    public function __isset($name)
    {
        return isset($this->_components[$name]);
    }

    public function __unset($name)
    {
        throw new InvalidCallException(
            "Unsetting application property: ".$name);
    }

    public function __call($name, $args)
    {
        if(!isset($this->_components[$name]))
            $this->loadComponent($name);
        if(method_exists($this->_components[$name], "call"))
            return call_user_func_array(
                [$this->_components[$name], "call"], $args);
        else
            throw new InvalidCallException(
                "Component doesnt have method 'call': $name");
    }

    public function test()
    {
        header("Content-type: text/plain");
        print_r(array_keys($this->registered_components));
        exit;
    }

    public function getType()
    {
        return Str::method2cmd(
            preg_replace('/^.*\\\|App$/', "", get_called_class()));
    }

    public function getAvailableModules()
    {
        if(is_null($this->_available_modules)) {
            $this->_available_modules = [];
            foreach(Loader::dirs() as $dir) {
                $dir = self::getAlias($dir);
                $dirs = array_filter(glob("$dir/*/modules/*", GLOB_BRACE), "is_dir");
                foreach($dirs as $mdir) {
                    $mdir = preg_replace("/^".preg_quote($dir, "/")."/", "", $mdir);
                    $mdir = trim($mdir, "\\/");
                    $parts = preg_split('/[\/\\\\]/', $mdir);
                    if(count($parts) !== 3)
                        continue;
                    $mname = $parts[0].":".$parts[2];
                    $cname = ModuleBase::getClass($mname, false);
                    if($cname)
                        $this->_available_modules[$mname] = $cname::instance(false);
                }
            }
        }
        return $this->_available_modules;
    }

    public function getEnabledModules()
    {
        $modules = [];
        foreach($this->modules_by_name as $name => $module) {
            if($module->isEnabled())
                $modules[$name] = $module;
        }
        return $modules;
    }

    public function getModulesByClass()
    {
        return $this->modules_by_class;
    }

    public function getModulesByName()
    {
        return $this->modules_by_name;
    }

    public function hasModule($name)
    {
        return isset($this->modules_by_name[$name]);
    }

    public function getClassname()
    {
        return get_called_class();
    }

    public function getClassbasename()
    {
        return preg_replace('/^.*\\\|App$/', "", get_called_class());
    }

    public function getAppNamespace()
    {
        return $this->_app_namespace;
    }

    public function getException()
    {
        return $this->_exception;
    }

    public function setException($e)
    {
        $this->_exception = $e;
    }

    public function getTime()
    {
        return $this->time();
    }

    public function time()
    {
        return time() + $this->config("system.time_offset", 0);
    }

    public function setLocale($locale)
    {
        if(function_exists('bindtextdomain')) {
            putenv("LC_ALL=$locale.UTF-8");
            setlocale(LC_ALL, $locale.'.UTF-8');
            bindtextdomain($this->app_namespace, APPROOT."/locale");
            textdomain($this->app_namespace);
        }
        $this->locale = $locale;
        $this->trigger("localeChanged", $locale);
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function configOrig($key = null, $default = null)
    {
        return Arr::getItem($this->_original_config, $key, $default);
    }

    public function config($key = null, $default = null)
    {
//        if(!is_null($key) && isset($this->_changed_config[$key]))
//            return $this->_changed_config[$key];
//        else
            return Arr::getItem($this->_config, $key, $default);
    }

    public function configWrite($key, $value)
    {
        if(!$this->configAccessWrite($key))
            return false;
//        $this->_changed_config[$key] = $value;
        Arr::setItem($this->_config, $key, $value);
        return true;
    }

    public function configAppend($key, $value)
    {
        if(!$this->configAccessAppend($key))
            return false;
        $val = $this->config($key, []);
        if(is_array($value))
            $val = array_merge($val, $value);
        else
            $val[] = $value;
        Arr::setItem($this->_config, $key, $val);
        return true;
    }

    public function configAccessWrite($key)
    {
        $val = $this->configPermissionsValue($key);
        return $val === "write" || $val === "all" ? true : false;
    }

    public function configAccessAppend($key)
    {
        $val = $this->configPermissionsValue($key);
        return $val === "append" || $val === "all" ? true : false;
    }

    /**
     * @return AppBase|WebApp|ConsoleApp|TaskApp
     */
    public static final function app()
    {
        return static::$_app;
    }

    public static function powered()
    {
        return "Powered by EQ Framework ".self::version();
    }

    public static function version()
    {
        $version = @file_get_contents(EQROOT."/version");
        if($version)
            return $version;
        try {
            $fname = EQROOT."/composer.json";
            if(file_exists($fname)) {
                $data = @json_decode(@file_get_contents($fname), JSON_OBJECT_AS_ARRAY);
                if($data && isset($data['version']) && $data['version'])
                    return $data['version'];
                elseif($data && isset($data['extra']['branch-alias']['dev-master'])
                        && $data['extra']['branch-alias']['dev-master'])
                    return $data['extra']['branch-alias']['dev-master'];
            }
        }
        catch(Exception $e) {}
        try {
            $repo = new Glip_Git(EQROOT."/.git");
            $bname = $repo->getCurrentBranch();
            $branch = $repo->getTip($bname);
            $commit = $repo->getObject($branch);
            $hash = substr(Glip_Binary::sha1_hex($branch), 0, 7);
            return "[$bname: $hash - ".$commit->summary
                ." (".date("y-m-d", $commit->committer->time).")]";
        }
        catch(Exception $e) {
            return "[UNKNOWN VERSION]";
        }
    }

    public static final function __callStatic($name, $args)
    {
        if(isset(static::$static_methods[$name]))
            $method = static::$static_methods[$name];
        elseif(isset(static::$default_static_methods[$name]))
            $method = static::$default_static_methods[$name];
        else
            throw new InvalidCallException(
                "Static method does not exists: $name");
        return call_user_func_array($method, $args);
    }

    public static function assert($assertion, $description = null)
    {
        if(!$assertion)
            throw new AssertionFailedException($assertion, $description);
    }

    /**
     * @param mixed $msg, ...
     */
    public static function log($msg)
    {
        \EQ::app()->trigger("log", func_get_args());
    }

    /**
     * @param mixed $msg, ...
     */
    public static function warn($msg)
    {
        \EQ::app()->trigger("warn", func_get_args());
    }

    /**
     * @param mixed $msg, ...
     */
    public static function err($msg)
    {
        \EQ::app()->trigger("err", func_get_args());
    }

    /**
     * @param string $msg
     */
    public static function todo($msg)
    {
        // TODO check for repeats
        \EQ::app()->trigger("todo", $msg);
    }

    /**
     * @param string $msg
     */
    public static function fixme($msg)
    {
        // TODO check for repeats
        \EQ::app()->trigger("fixme", $msg);
    }

    /**
     * @param mixed $var, ...
     */
    public static function dump($var)
    {
        \EQ::app()->trigger("dump", func_get_args());
    }

    public static function cache($name = null, $value = null)
    {
        if(is_null($name))
            return \EQ::app()->cache;
        else
            return \EQ::app()->cache->data($name, $value);
    }

    protected static function systemStaticMethods()
    {
        return [
            'log' => function() {
                \EQ::app()->trigger("log", func_get_args());
            },
        ];
    }

    protected static function defaultStaticMethods()
    {
        return [
            't' => function($text) { return $text; },
            'k' => function($key)  { return $key;  },
            // 'log' => function($msg) {  },
        ];
    }

    protected function configPermissions()
    {
        return EQ_RECOVERY ? [] : [
            'modules.*' => "all",
            'site.*' => "all",
        ];
    }

    protected function loadModules()
    {
        $classes = [];
        $modules_o = $this->config("modules", []);
        $init_method = $this->type."Init";
        $ready_method = $this->type."Ready";
        foreach($modules_o as $name => $conf) {
            if(!isset($conf['enabled']) || !$conf['enabled'])
                continue;
            $cname = ModuleBase::getClass($name);
            $classes[$name] = $cname;
            $cname::preInit();
        }
        $modules = $this->config("modules", []);
        foreach($modules as $name => $conf) {
            if(isset($modules_o[$name]['enabled'])) {
                if(!$modules_o[$name]['enabled'])
                    continue;
            }
            elseif(!isset($conf['enabled']) || !$conf['enabled'])
                continue;
            $cname = isset($classes[$name]) ? $classes[$name] : ModuleBase::getClass($name, false);
            if($cname) {
                $this->trigger("modules.$name.init");
                $this->trigger("modules.$name.{$this->type}Init");
                $module = $this->loadModule($name, $cname);
                if(method_exists($module, $init_method))
                    $module->{$init_method}();
                $module->ready();
                $this->trigger("modules.$name.ready");
            }
            else {
                \EQ::warn("Module not found: $name");
                $this->trigger("moduleNotFound", $name);
            }
        }
        foreach($modules as $name => $conf) {
            if((!isset($conf['enabled']) || !$conf['enabled'])
                    && (!isset($modules_o[$name]['enabled']) || !$modules_o[$name]['enabled']))
                continue;
            if(isset($this->modules_by_name[$name]))
                $this->trigger("modules.$name.ready", $this->modules_by_name[$name]);
        }
    }

    /**
     * @param string $name
     * @param string|ModuleBase $cname
     * @return \eq\base\ModuleBase
     */
    protected function loadModule($name, $cname)
    {
        if(isset($this->modules_by_name[$name]))
            return $this->modules_by_name[$name];
        $this->trigger("modules.$name.init");
        $module = $cname::instance(true);
        $this->processModuleDependencies($module);
        $this->modules_by_name[$name] = $module;
        $this->modules_by_class[$cname] = $module;
//        $this->config_permissions['modules'][$name] = $module->configPermissions();
        $perms = $module->configPermissions();
        if(is_array($perms)) {
            foreach($module->configPermissions() as $pname => $pvalue)
                $this->config_permissions["modules.$name.$pname"] = $pvalue;
        }
        self::setAlias("@modules.$name", $module->location);
        $this->registerModuleComponents($module);
        $this->registerModuleStaticMethods($module);
        return $module;
    }

    protected function processModuleDependencies(ModuleBase $module)
    {
        foreach($module->depends as $mname) {
            $cname = ModuleBase::getClass($mname, false);
            if($cname)
                $this->loadModule($mname, $cname);
            else {
                $message = \EQ::t("Module not found").": $mname";
                $module->addError($message);
                \EQ::err($message);
            }
        }
    }

    protected function registerModuleComponents(ModuleBase $module)
    {
        $components = $module->components;
        if(!is_array($components)) {
            $message = "Invalid module components: ".$module->name;
            $module->addError($message);
            \EQ::err($message);
            return;
        }
        foreach($components as $name => $conf)
            $this->registerModuleComponent($module, $name, $conf);
    }

    protected function registerModuleComponent(ModuleBase $module, $name, $conf)
    {
        $err = function($message) use($module) {
            $message .= " (module: {$module->name})";
            $module->addError($message);
            \EQ::err($message);
            return false;
        };
        if(!is_string($name) || !$name)
            return $err("Invalid component name: $name");
        if($this->isComponentRegistered($name))
            return $err("Component already registered: $name");
        if(!is_array($conf)) {
            if((!is_string($conf) && !is_object($conf)) || !$conf) {
                return $err("Invalid component: $name");
            }
            $this->registerComponent($name, $conf);
        }
        else {
            if(!isset($conf['class']) || !$conf['class']
                    || (!is_string($conf['class']) && !is_object($conf['class']))) {
                return $err("Invalid component class: $name");
            }
            $this->registerComponent(
                $name,
                $conf['class'],
                isset($conf['config']) ? $conf['config'] : null,
                isset($conf['preload']) ? $conf['preload'] : false
            );
        }
        return true;
    }

    protected function registerModuleStaticMethods(ModuleBase $module)
    {
        $methods = $module->static_methods;
        if(!is_array($methods)) {
            $message = "Invalid module static methods: ".$module->name;
            $module->addError($message);
            \EQ::err($message);
            return;
        }
        foreach($methods as $name => $method)
            $this->registerModuleStaticMethod($module, $name, $method);
    }

    protected function registerModuleStaticMethod(ModuleBase $module, $name, $method)
    {
        $err = function($message) use($module) {
            $message .= " (module: {$module->name})";
            $module->addError($message);
            \EQ::err($message);
            return false;
        };
        if(!is_string($name) || !$name)
            return $err("Invalid static method name: $name");
        if($this->isStaticMethodRegistered($name))
            return $err("Static method already registered: $name");
        if(!is_callable($method))
            return $err("Static method is not callable: $name");
        $this->registerStaticMethod($name, $method);
        return true;
    }

    public function isComponentRegistered($name)
    {
        return isset($this->_components[$name])
            || isset($this->system_components[$name])
            || isset($this->_config['components'][$name])
            || isset($this->registered_components[$name]);
    }

    protected function registerComponent($name, $class, $config = null, $preload = false)
    {
        if($this->isComponentRegistered($name))
            throw new ComponentException("Component already registered: $name");
        if($preload || is_object($class)) {
            $obj = is_object($class) ? $class : new $class($config);
            $this->_components[$name] = $obj;
        }
        else
            $this->registered_components[$name] = [
                'class' => $class,
                'config' => $config,
            ];
        return $this;
    }

    public function isStaticMethodRegistered($name)
    {
        return method_exists(get_called_class(), $name) || isset(static::$static_methods[$name]);
    }

    protected function registerStaticMethod($name, $method)
    {
        if($this->isStaticMethodRegistered($name))
            throw new ComponentException("Static method already exists: $name");
        if(!is_callable($method))
            throw new ComponentException("Method is not callable: $name");
        static::$static_methods[$name] = $method;
        return $this;
    }

    protected function directories()
    {
        return [
            "@app/runtime" => 0775,
        ];
    }

    protected function defaultComponents()
    {
        return [];
    }

    protected function systemComponents()
    {
        return [
            'cache' => [
                'class' => 'eq\base\Cache',
                'config' => [],
            ],
            'db' => [
                'class' => 'eq\db\Pool',
                'config' => $this->config("db", []),
            ],
        ];
    }

    protected function loadComponent($name)
    {
        if(!$this->system_components)
            $this->system_components = $this->systemComponents();
        if(isset($this->_config['components'][$name]))
            $config = $this->_config['components'][$name];
        elseif(isset($this->system_components[$name]))
            $config = $this->system_components[$name];
        elseif(isset($this->registered_components[$name]))
            $config = $this->registered_components[$name];
        elseif(isset($this->default_components[$name]))
            $config = $this->default_components[$name];
        else
            throw new ComponentException("Undefined component: $name");
        if(!is_array($config))
            throw new InvalidConfigException(
                "Invalid component config: $name");
        isset($config['config']) or $config['config'] = [];
        if(!isset($config['class']) || !is_array($config['config']))
            throw new InvalidConfigException(
                "Invalid component config: $name");
        $class = $config['class'];
        if(is_object($class))
            $this->_components[$name] = $class;
        else {
            if(!Loader::classExists($class))
                throw new InvalidConfigException(
                    "Component class not found: $name");
            $this->_components[$name] = new $class($config['config']);
        }
    }

    protected function processConfig($config)
    {
        $app_type = $this->type;
        $config = Arr::merge(require EQROOT."/config.default.php", $config);
        $config = $this->normalizeConfigValues($config, [
            'components' => [],
            'modules' => [],
        ]);
        $app_config = isset($config[$app_type]) ? $config[$app_type] : [];
        $config = $this->mergeConfig($config, $app_config, [
            'components',
            'modules',
        ]);
        $config['system'] = $this->normalizeConfigValues($config['system'], [
            'src_dirs' => [],
            'app_namespace' => "eq",
        ]);
        if(!strlen($config['system']['app_namespace']))
            throw new InvalidConfigException(
                "Ivalid config value: system.app_namespace");
        foreach($config['modules'] as $key => $conf) {
            if(is_null($conf))
                $config['modules'][$key] = [];
        }
        $this->_app_namespace = $config['system']['app_namespace'];
        $this->_config = $config;
        $this->_original_config = $config;
    }

    protected function mergeConfig($config, $app_config, $keys)
    {
        foreach($keys as $key) {
            $val = isset($app_config[$key]) ? $app_config[$key] : [];
            $config[$key] = Arr::merge($config[$key], $val);
        }
        return $config;
    }

    protected function normalizeConfigValues($config, $values)
    {
        foreach($values as $value => $default)
            if(!isset($config[$value])
                    || gettype($config[$value]) !== gettype($default))
                $config[$value] = $default;
        return $config;
    }

    protected function assertConfigValue($config, $value)
    {
        
    }

    protected function configPermissionsValue($key, $default = false)
    {
        $val = $default;
        if(isset($this->config_permissions[$key])) {
            $val = $this->config_permissions[$key];
        }
        else {
            foreach($this->config_permissions as $name => $value) {
                if(fnmatch($name, $key)) {
                    $val = $value;
                    break;
                }
            }
        }
        return $val;
    }

    protected function processConfigPermissions()
    {
        $config = $this->configKeysRecursive($this->config_permissions);
        $this->config_permissions = $config;
    }

    protected function configKeysRecursive($config, $prefix = "")
    {
        $res = [];
        if($prefix)
            $prefix .= ".";
        foreach($config as $name => $value) {
            if(is_array($value))
                $res = array_merge($res, $this->configKeysRecursive(
                    $value, $prefix.$name));
            else
                $res[$prefix.$name] = $value;
        }
        return $res;
    }

}
