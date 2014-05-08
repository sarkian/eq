<?php

namespace eq\base;

use EQ;
use eq\helpers\Str;

/**
 * @property string name
 * @property string shortname
 * @property string namespace
 * @property string location
 * @property string url_prefix
 * @property array depends
 */
abstract class ModuleBase extends ModuleAbstract
{

    use TObject;

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
     * @internal param bool $noinit
     * @return ModuleBase
     */
    protected static final function instance($enable = false)
    {
        $modules = EQ::app()->modules_by_class;
        $cname = get_called_class();
        return isset($modules[$cname]) ? $modules[$cname] : new $cname($enable);
    }


    private $_name;
    private $_shortname;
    private $_namespace;
    private $_location;
    private $_enabled = false;

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

    protected final function registerStaticMethod($name, $method)
    {
        EQ::app()->registerStaticMethod($name, $method);
    }

    protected function init()
    {

    }

}
