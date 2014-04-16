<?php
/**
 * Last Change: 2014 Apr 16, 13:54
 */

namespace eq\base;

use eq\helpers\Str;
use eq\helpers\Arr;
use eq\helpers\Git;
use eq\helpers\FileSystem;

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
    protected $_app_namespace;
    protected $_components = [];
    protected $_exception;

    protected $system_components = [];
    protected $registered_components = [];
    protected $default_components = [];
    protected $loaded_modules = [];

    protected $locale = "en_US";

    abstract public function run();
    abstract public function processFatalError($err);
    abstract public function processException($e);
    abstract public function processUncaughtException($e);

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
        static::$default_static_methods = self::defaultStaticMethods();
        $this->system_components = $this->systemComponents();
        foreach($this->system_components as $name => $component) {
            if(Arr::getItem($component, "preload", false))
                $this->loadComponent($name);
        }
        try {
            foreach($this->config("modules", []) as $mod => $conf) {
                $cname = ModuleBase::getClass($mod);
                $cname::init($conf);
                $this->loaded_modules[$mod] = $cname;
            }
            $this->trigger("ready");
        }
        catch(ExceptionBase $e) {
            $this->processException($e);
        }
        catch(\Exception $ue) {
            $this->processUncaughtException($ue);
        }
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
            return $this->TObject_set($name, $value);
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

    public function getType()
    {
        return Str::method2cmd(
            preg_replace("/^.*\\\|App$/", "", get_called_class()));
    }

    public function getLoadedModules()
    {
        return $this->loaded_modules;
    }

    public function getClassname()
    {
        return get_called_class();
    }

    public function getClassbasename()
    {
        return preg_replace("/^.*\\\|App$/", "", get_called_class());
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
        $this->trigger("localeChanged", [$locale]);
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function config($key = null, $default = null)
    {
        return Arr::getItem($this->_config, $key, $default);
    }

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
        $fname = EQROOT."/version";
        if($version = @file_get_contents($fname))
            return "v".$version;
        $repo = new \glip\Git(EQROOT."/.git");
        $bname = $repo->getCurrentBranch();
        $branch = $repo->getTip($bname);
        $commit = $repo->getObject($branch);
        $hash = substr(\glip\Binary::sha1_hex($branch), 0, 7);
        return "[$bname: $hash - ".$commit->summary
            ." (".date("y-m-d", $commit->committer->time).")]";
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

    protected static function defaultStaticMethods()
    {
        return [
            't' => function($text) { return $text; },
            'k' => function($key)  { return $key;  },
        ];
    }

    protected function registerComponent($name, $class,
        $config = [], $preload = false)
    {
        if(isset($this->_components[$name]) 
                || isset($this->system_components[$name]) 
                || isset($this->_config['components'][$name])
                || isset($this->registered_components[$name]))
            throw new ComponentException("Component already registered: $name");
        if($preload) {
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

    protected function registerStaticMethod($name, $method)
    {
        if(method_exists(get_called_class(), $name))
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
            'db' => [
                'class' => 'eq\db\Pool',
                'config' => $this->config("db", []),
            ],
        ];
    }

    protected function loadComponent($name)
    {
        $config = [];
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
        $this->_app_namespace = $config['system']['app_namespace'];
        $this->_config = $config;
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

}
