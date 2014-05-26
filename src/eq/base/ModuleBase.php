<?php

namespace eq\base;

use EQ;
use eq\helpers\Str;

/**
 * @property string name
 * @property string title
 * @property string description
 * @property string shortname
 * @property string namespace
 * @property string location
 * @property string url_prefix
 * @property array depends
 * @property array errors
 * @property array warnings
 */
abstract class ModuleBase extends ModuleAbstract
{

    use TObject;

    private static $_instances = [];

    private $errors = [];
    private $warnings = [];

    /**
     * @param string $name
     * @param bool $except
     * @return static ModuleBase|bool
     * @throws ModuleException
     */
    public static final function getClass($name, $except = true)
    {
        $path = explode(":", $name);
        $mname = array_pop($path);
        $bname = Str::cmd2method($mname)."Module";
        $path = $path ? implode("\\", $path)."\\" : "";
        $cname = $path."modules\\$mname\\".$bname;
        if(Loader::classExists($cname)) {
            $parents = class_parents($cname);
            if(isset($parents['eq\base\ModuleBase']))
                return $cname;
            elseif($except)
                throw new ModuleException(
                    'Module class must be inherited from eq\base\ModuleBase: '.$cname);
            else
                return false;
        }
        elseif($except)
            throw new ModuleException("Module class not found: $name");
        else
            return false;
    }

    /**
     * @param bool $enable
     * @return ModuleBase
     */
    protected static final function instance($enable = false)
    {
        $cname = get_called_class();
        if(!isset(self::$_instances[$cname]) || !self::$_instances[$cname] instanceof $cname)
            self::$_instances[$cname] = new $cname($enable);
        return self::$_instances[$cname];
    }

    protected static function preInit()
    {

    }


    private $_name;
    private $_shortname;
    private $_namespace;
    private $_location;
    private $_enabled = false;

    protected $title = "";
    protected $description = "";

    public function getTitle()
    {
        if(is_array($this->title) && $this->title) {
            $titles = $this->title;
            if(isset($titles[EQ::app()->locale]))
                return $titles[EQ::app()->locale];
            elseif(isset($titles['en_US']))
                return $titles['en_US'];
            else
                return array_shift($titles);
        }
        elseif(is_string($this->title) && $this->title)
            return $this->title;
        else
            return $this->name;
    }

    public function getDescription()
    {
        if(is_array($this->description) && $this->description) {
            $descrs = $this->description;
            if(isset($descrs[EQ::app()->locale]))
                return $descrs[EQ::app()->locale];
            elseif(isset($descrs['en_US']))
                return $descrs['en_US'];
            else
                return array_shift($descrs);
        }
        elseif(is_string($this->description))
            return $this->description;
        else
            return "";
    }

    private final function __construct($enable = false)
    {
        if($enable) {
            $this->init();
            $this->_enabled = true;
        }
    }

    public final function isEnabled()
    {
        return $this->_enabled;
    }

    public final function isEnabledAsDependency()
    {
        return $this->_enabled && !EQ::app()->config("modules.".$this->name.".enabled");
    }

    public final function canDisable()
    {
        return !$this->isEnabledAsDependency()
            && !EQ::app()->configOrig("modules.".$this->name.".enabled");
    }

    public final function getName()
    {
        if(!$this->_name) {
            $parts = explode("\\", $this->namespace);
            if(count($parts) === 3 && $parts[1] === "modules")
                $this->_name = $parts[0].":".$parts[2];
            else
                throw new ModuleException("Unable to get module name: ".get_class($this));
        }
        return $this->_name;
    }

    public final function getShortname()
    {
        if(!$this->_shortname) {
            $parts = explode(":", $this->name);
            if(count($parts) < 2)
                throw new ModuleException("Invalid module name: ".$this->name);
            $this->_shortname = array_pop($parts);
        }
        return $this->_shortname;
    }

    public final function getNamespace()
    {
        if(!$this->_namespace)
            $this->_namespace = Str::classNamespace(get_called_class());
        return $this->_namespace;
    }

    public final function getLocation()
    {
        if(!$this->_location) {
            $cname = get_called_class();
            $fname = Loader::classLocation($cname);
            if(!$fname)
                throw new ModuleException("Unable to get module location: $cname");
            $this->_location = dirname($fname);
        }
        return $this->_location;
    }

    public final function getErrors()
    {
        return $this->errors;
    }

    public final function getWarnings()
    {
        return $this->warnings;
    }

    public final function findClass($classname)
    {
        $name = trim(str_replace(".", "\\", $classname), "\\");
        $cname = $this->getNamespace()."\\".$name;
        if(!Loader::classExists($cname))
            throw new ModuleException("Class not found: $classname");
        return $cname;
    }

    public final function config($key = null, $default = null)
    {
        return EQ::app()->config($this->configKey($key), $default);
    }

    public final function configWrite($key, $value)
    {
        EQ::app()->configWrite($this->configKey($key), $value);
    }

    public final function configAppend($key, $value)
    {
        EQ::app()->configAppend($this->configKey($key), $value);
    }

    public final function configAccessWrite($key)
    {
        return EQ::app()->configAccessWrite($this->configKey($key));
    }

    public final function configAccessAppend($key)
    {
        return EQ::app()->configAccessAppend($this->configKey($key));
    }

    public final function bind($events, $callable)
    {
        EQ::app()->bind($this->events($events), $callable);
    }

    public final function unbind($events, $callable = null)
    {
        EQ::app()->unbind($this->events($events), $callable);
    }

    public final function trigger($events, $args = [])
    {
        EQ::app()->trigger($this->events($events), $args);
    }

    public final function disableEvents($events)
    {
        EQ::app()->disableEvents($this->events($events));
    }

    public final function route($route)
    {
        return implode(".", ["modules", $this->name, $route]);
    }

    public function getUrlPrefix()
    {
        return "";
    }

    public function getDepends()
    {
        return [];
    }

    protected final function configKey($key)
    {
        return implode(".", ["modules", $this->name, $key]);
    }

    protected final function events($events)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $i => $event)
            $events[$i] = $this->configKey($event);
        return $events;
    }

    protected final function registerStaticMethod($name, $method)
    {
        EQ::app()->registerStaticMethod($name, $method);
    }

    protected final function addError($message)
    {
        $this->errors[] = $message;
    }

    protected final function addWarning($message)
    {
        $this->warnings[] = $message;
    }

    protected function init()
    {

    }

}
