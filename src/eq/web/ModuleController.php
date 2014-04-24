<?php
/**
 * Last Change: 2014 Apr 24, 20:54
 */

namespace eq\web;

use EQ;
use eq\helpers\Str;
use eq\helpers\Path;
use eq\web\ControllerException;
use eq\base\ModuleException;
use eq\base\Loader;

abstract class ModuleController extends Controller
{

    private $_module_namespace;
    private $_module_class;
    private $_module_name;

    public function getModuleClass()
    {
        if(!$this->_module_class) {
            $cname = get_called_class();
            $parts = array_reverse(explode('\\', $cname));
            $key = array_search("controllers", $parts);
            if($key !== false && isset($parts[$key + 1])) {
                $name = $parts[$key + 1];
                $parts = array_slice($parts, $key + 1);
                $ns = implode("\\", array_reverse($parts));
                $cname = $ns."\\".Str::var2method($name)."Module";
                if(Loader::classExists($cname)) {
                    $this->_module_namespace = $ns;
                    $this->_module_class = $cname;
                    $this->_module_name = $name;
                    return $cname;
                }
            }
            throw new ModuleException("Module class not found");
        }
        return $this->_module_class;
    }

    public function getModuleName()
    {
        if(!$this->_module_name)
            $this->getModuleClass();
        return $this->_module_name;
    }

    public function getModuleNamespace()
    {
        if(!$this->_module_namespace)
            $this->getModuleClass();
        return $this->_module_namespace;
    }

    public function getModuleLocation()
    {
        return EQ::app()->module($this->module_name)->location;
    }

    public function config($key = null, $default = null)
    {
        $key = implode(".", ["modules", $this->module_name, $key]);
        return EQ::app()->config($key, $default);
    }

    protected function findViewFile($view_file)
    {
        $fname = Path::join([$this->module_location, "views", $view_file.".php"]);
        return file_exists($fname) ? $fname : parent::findViewFile($view_file);
    }

    protected function findTemplate()
    {
        $tpl = $this->template;
        if(!$tpl)
            return false;
        $fname = Path::join([$this->module_location, "templates", "$tpl.php"]);
        return file_exists($fname) ? $fname : parent::findTemplate();
    }

}
