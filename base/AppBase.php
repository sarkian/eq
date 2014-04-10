<?php

namespace eq\base;

    defined('EQROOT') or define('EQROOT', realpath(__DIR__.'/..'));
    defined('EQ_DBG') or define('EQ_DBG', true);
    defined('EQ_WARNING') or define('EQ_WARNING', true);
    defined('EQ_NOTICE') or define('EQ_NOTICE', true);
    defined('EQ_DEPRECATED') or define('EQ_DEPRECATED', true);
    defined('EQ_STRICT') or define('EQ_STRICT', true);

    require_once EQROOT.'/exceptions/ExceptionBase.php';
    require_once EQROOT.'/exceptions/LoaderException.php';
    require_once EQROOT.'/base/Loader.php';
    Loader::loadDir(EQROOT.'/base');
    Loader::loadDir(EQROOT.'/misc'); // TO_DO WTF?
    spl_autoload_register(['\\eq\\base\\Loader', 'loadClass']);

    use \eq\base\AppException;

    abstract class AppBase
    {

        // TO_DO WTF?
        use default_config;

        use traits\Alias;
        use traits\Event;

        const TYPE_WEB = 1;
        const TYPE_CONSOLE = 2;

        public $config;
        public $app_namespace;

        protected $last__exception;
        protected $_current_locale;
        protected $component_methods = [];

        protected static $_app = null;

        public function __construct($config)
        {
            $this->bind('onException', function($e) {
                $this->last__exception = $e;
            });
            $this->config = $this->normalizeConfig($config);
            $this->app_namespace = $this->config['system']['app_namespace'];
            if(!$this->app_namespace || !is_string($this->app_namespace)
                || !strlen($this->app_namespace) || strpos(' ', $this->app_namespace) !== false)
                die("Invalid app_namespace");
            date_default_timezone_set($this->config['system']['default_timezone']);
            $this->setLocale($this->config['system']['default_locale']);
            if(is_array($this->config['components']['preload'])) {
                foreach($this->config['components']['preload'] as $component => $class)
                    $this->{$component} = new $class;
            }
            if(\is_array($this->config['datatypes'])) {
                foreach($this->config['datatypes'] as $constname => $classname)
                    $classname::registerConstant($constname);
            }
        }

        public function getLastException()
        {
            return $this->last__exception;
        }

        public static function createWebApp($config_file)
        {
            return self::createApp(self::TYPE_WEB, $config_file);
        }

        public static function createConsoleApp($config_file)
        {
            return self::createApp(self::TYPE_CONSOLE, $config_file);
        }

        /**
         *
         * @return eq\web\WebApp|eq\console\ConsoleApp
         */
        public static function app()
        {
            return self::$_app;
        }

        protected static function createApp($type, $config_file)
        {
            if(self::$_app) return null;
            $config = self::loadConfig($config_file);
            set_error_handler(['\eq\base\ErrorHandler', 'onError']);
            set_exception_handler(['\eq\base\ErrorHandler', 'onException']);
            register_shutdown_function(['\eq\base\ErrorHandler', 'onShutdown']);
            switch($type) {
                case self::TYPE_WEB:
                    Loader::loadDir(EQROOT.'/web');
                    return new \eq\web\WebApp($config);
                case self::TYPE_CONSOLE:
                    Loader::loadDir(EQROOT.'/console');
                    return new \eq\console\ConsoleApp($config);
                default:
                    die("Unknown application type: $type");
            }
        }

        public function setLocale($locale)
        {
            if(!function_exists('bindtextdomain')) return;
            putenv("LC_ALL=$locale.UTF-8");
            setlocale(LC_ALL, "$locale.UTF-8");
            bindtextdomain($this->app_namespace, APPROOT.'/locale');
            textdomain($this->app_namespace);
            $this->_current_locale = $locale;
        }

        public function getCurrentLocale()
        {
            return $this->_current_locale;
        }

        public function getLangName($locale = null)
        {
            if(!$locale) $locale = $this->_current_locale;
            if(!isset($this->config['locales'][$locale])) return '';
            return $this->config['locale'][$locale];
        }

        public function time()
        {
            return time() + $this->config['system']['time_offset'];
        }

        public function __get($name)
        {
            $this->loadComponent($name);
            return $this->{$name};
        }

        public function __call($name, $args)
        {
            if(isset($this->component_methods[$name]))
                return call_user_func_array($this->component_methods[$name], $args);
            $this->loadComponent($name);
            if($this->{$name} instanceof \eq\base\Component)
                return $this->{$name}->call($args);
            else
                throw new AppException("Component must be inherited from \\eq\\base\\Component: $name");
        }  

        public function registerComponentMethod($name, $callable)
        {
            if(isset($this->component_methods[$name]))
                throw new AppException("Component method already registered");
            if(!\is_callable($callable))
                throw new AppException("Argument is not callable");
            $this->component_methods[$name] = $callable;
        }

        protected function loadComponent($name)
        {
            if(isset($this->{$name})) return;
            if(isset($this->config['components'][$name])) {
                try {
                    $this->{$name} = new $this->config['components'][$name];
                }
                catch(\eq\base\LoaderException $e) {
                    throw new AppException("Component class not found: {$this->config['components'][$name]}");
                }
            }
            else throw new AppException("Undefined component: $name");
        }

        public abstract function run($argc = null, $argv = null);

        public abstract function processFatalError($err);

        public abstract function processUncaughtException($e);

        public static function loadConfig($fname)
        {
            if(!\function_exists('yaml_parse_file'))
                throw new AppException("YAML parser not found");
            if(!\file_exists($fname))
                throw new AppException("Configuration file not found: $fname");
            $config = \yaml_parse_file($fname);
            if(isset($config['_include']) && \is_array($config['_include'])) {
                $includes = [];
                foreach($config['_include'] as $file)
                    $includes[] = self::loadConfig(\dirname($fname).'/'.$file.'.yml');
                $includes[] = $config;
                $config = \call_user_func_array('array_merge_recursive', $includes);
                unset($config['_include']);
            }
            return $config;
        }

        protected function normalizeConfig($config)
        {
            foreach($this->_default_config as $name => $value)
                $config[$name] = isset($config[$name]) ? \eq\misc\extend($config[$name], $value) : $value;
            return $config;
        }

        protected function addCallback($name, $function)
        {
            if(!is_callable($function))
                throw new \eq\base\AppException("Argument is not callable");
            $this->{$name}[] = $function;
        }

        protected function processCallbacks($name)
        {
            $functions = $this->{$name};
            $this->{$name} = [];
            foreach($functions as $function)
                call_user_func($function);
        }

    }
