<?php
/**
 * Last Change: 2014 Apr 24, 04:18
 */

namespace eq\base;

use EQ;
use eq\helpers\Str;

abstract class ModuleBase extends ModuleAbstract
{

    use TObject;

    public static final function getClass($name)
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
        if(!isset($parents["eq\base\ModuleBase"]))
            throw new ModuleException(
                "Module class must be inherited from eq\base\ModuleBase");
        return $cname;
    }

    protected static final function instance()
    {
        $modules = EQ::app()->modules_by_class;
        $cname = get_called_class();
        return isset($modules[$cname]) ? $modules[$cname] : new $cname();
    }

    private $_name;
    private $_namespace;
    private $_location;

    private final function __construct()
    {
        $this->init();
    }

    public final function getName()
    {
        if(!$this->_name)
            $this->_name = Str::method2var(preg_replace("/Module$/", "", 
                Str::classBasename(get_called_class())));
        return $this->_name;
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
            $fname = Loader::classLocation(get_called_class());
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
