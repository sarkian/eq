<?php

namespace eq\base;

use EQ;
use eq\helpers\Str;

/**
 * @property string name
 * @property string shortname
 * @property string namespace
 * @property string location
 */
abstract class ModuleBase extends ModuleAbstract
{

    use TObject;

    // TODO: use ":" as module path separator
    /**
     * @param string $name
     * @param bool $except
     * @return static ModuleBase|bool
     * @throws ModuleException
     */
    public static final function getClass($name, $except = true)
    {
        $path = explode("/", $name);
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

    public static final function getClass_old($name)
    {
        $path = explode(".", $name);
        array_push($path, 
            Str::cmd2var($name)."\\".Str::cmd2method(
                array_pop($path))."Module");
        $cbasename = implode("\\", $path);
        $cname = EQ::app()->app_namespace."\\modules\\".$cbasename;
        if(Loader::classExists($cname))
            return $cname;
        $cname = 'eq\modules\\'.$cbasename;
        if(!Loader::classExists($cname))
            throw new ModuleException("Module class not found: $name");
        $parents = class_parents($cname);
        if(!isset($parents['eq\base\ModuleBase']))
            throw new ModuleException(
                'Module class must be inherited from eq\base\ModuleBase');
        return $cname;
    }

    /**
     * @return ModuleBase
     */
    protected static final function instance()
    {
        $modules = EQ::app()->modules_by_class;
        $cname = get_called_class();
        return isset($modules[$cname]) ? $modules[$cname] : new $cname();
    }


    private $_name;
    private $_shortname;
    private $_namespace;
    private $_location;

    private final function __construct()
    {
        $this->init();
    }

    public final function getName()
    {
        if(!$this->_name) {
            $parts = explode("\\", $this->namespace);
            if(count($parts) === 3 && $parts[1] === "modules")
                $this->_name = $parts[0]."/".$parts[2];
            else
                throw new ModuleException("Unable to get module name: ".get_class($this));
        }
        return $this->_name;
    }

    public final function getShortname()
    {
        if(!$this->_shortname) {
            $parts = explode("/", $this->name);
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
        $key = implode(".", ["modules", $this->name, $key]);
        return EQ::app()->config($key, $default);
    }

    public function getUrlPrefix()
    {
        return "";
    }

    protected function init()
    {
        
    }

    protected function registerStaticMethod($name, $method)
    {
        EQ::app()->registerStaticMethod($name, $method);
    }

}
