<?php

namespace eq\base;

use EQ;

use eq\base\TObject;
use eq\helpers\Path;

/**
 * @property string module_class
 * @property string module_name
 * @property string module_namespace
 * @property string module_location
 * @property ModuleBase module
 */
trait TModuleClass
{

    use TObject;

    private $_module_class;
    private $_module_name;
    private $_module_namespace;
    private $_module_location;
    private $_module;

    public function getModuleClass()
    {
        if(!$this->_module_class)
            $this->_module_class = get_class(EQ::app()->module($this->module_name));
        return $this->_module_class;
    }

    public function getModuleName()
    {
        if(!$this->_module_name) {
            $cname = get_called_class();
            $parts = explode("\\", $cname);
            if(count($parts) > 2 && $parts[1] === "modules")
                $this->_module_name = $parts[0].":".$parts[2];
            else
                throw new ModuleException("Unable to get module name: $cname");
        }
        return $this->_module_name;
    }

    public function getModuleNamespace()
    {
        if(!$this->_module_namespace)
            $this->_module_namespace = EQ::app()->module($this->module_name)->namespace;
        return $this->_module_namespace;
    }

    public function getModuleLocation()
    {
        if(!$this->_module_location)
            $this->_module_location = EQ::app()->module($this->module_name)->location;
        return $this->_module_location;
    }

    public function getModule()
    {
        if(!$this->_module)
            $this->_module = EQ::app()->module($this->module_name);
        return $this->_module;
    }

    public function config($key = null, $default = null)
    {
        return $this->module->config($key, $default);
    }

    public function route($route)
    {
        return implode(".", ["modules", $this->module_name, $route]);
    }

    protected function findViewFile($view_file)
    {
        $fbname = Path::join([$this->module_location, "views", $view_file]);
        if(preg_match('/\.php$|\.twig$/', $view_file))
            $fname = $fbname;
        else {
            $fname = $fbname.".php";
            file_exists($fname) or $fname = $fbname.".twig";
        }
        return file_exists($fname) ? $fname : parent::findViewFile($view_file);
    }

    protected function findTemplate()
    {
        if(!isset($this->template))
            return false;
        $tpl = $this->template;
        if(!$tpl)
            return false;
        $fname = Path::join([$this->module_location, "templates", "$tpl.php"]);
        return file_exists($fname) ? $fname : parent::findTemplate();
    }

}
