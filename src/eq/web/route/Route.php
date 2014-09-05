<?php

namespace eq\web\route;

use EQ;
use eq\base\Cache;
use eq\base\TObject;
use eq\helpers\Str;
use eq\base\Loader;

/**
 * @property bool found
 * @property string controller_name
 * @property string action_name
 * @property string controller_class
 * @property string action_method
 * @property array vars
 */
class Route
{

    use TObject;

    protected $files = [];
    /**
     * @var RouteRule[]
     */
    protected $rules = [];

    protected $found = false;
    protected $controller_name;
    protected $action_name;
    protected $dynamic_controller;
    protected $dynamic_action;
    protected $vars = [];
    protected $controller_class;
    protected $action_method;

    public function __construct()
    {
        foreach(EQ::app()->config("web.route", []) as $file)
            $this->addFile(EQ::getAlias($file));
    }

    public function addFile($path, $url_prefix = "", $path_prefix = "")
    {
        if(isset($this->files[$path]))
            return;
        if($this->isFileModified($path, $url_prefix, $path_prefix)) {
            $file = new RouteFile($path, $url_prefix, $path_prefix);
            $this->rules = array_merge($this->rules, $file->rules);
            $fdata = [$url_prefix, $path_prefix, filemtime($path), $file->rules_data];
            Cache::setValue("route.files", $path, $fdata);
        }
        else {
            foreach(Cache::getValue("route.files", $path)[3] as $data) {
                $rule = new RouteRule();
                $rule->loadData($data);
                $this->rules[] = $rule;
            }
        }
        $this->files[$path] = [$url_prefix, $path_prefix];
    }

    public function getFound()
    {
        return $this->found;
    }

    public function getControllerName()
    {
        return $this->controller_name;
    }

    public function getActionName()
    {
        return $this->action_name;
    }

    public function getControllerClass()
    {
        return $this->controller_class;
    }

    public function getActionMethod()
    {
        return $this->action_method;
    }

    public function getVars()
    {
        return $this->vars;
    }

    public function redirect($path)
    {
        $this->found = false;
        $rules = $this->rules;
        $rule = new RouteRule();
        $rule->parsePath($path);
        $this->rules = [$rule];
        $this->processRequest();
        $this->vars = $_REQUEST;
        $this->rules = $rules;
    }

    public function createUrl($path, $vars = [], $url_vars = [])
    {
        if(is_array($path))
            $path = implode(".", $path);
        foreach($this->rules as $rule) {
            $url = $rule->createUrl($path, $vars);
            if(!$url)
                continue;
            if($url_vars) {
                $uvars = [];
                if(is_array($url_vars)) {
                    foreach($url_vars as $name => $value)
                        $uvars[] = is_string($name) ? $name."=".urlencode($value) : urlencode($value);
                    $url .= "?".implode("&", $uvars);
                }
                else
                    $url .= "?".$url_vars;
            }
            return $url;
        }
         throw new RouteException("Unable to create URL for path: $path");
    }

    public function processRequest($url = null)
    {
        $url or $url = explode("?", $_SERVER['REQUEST_URI'])[0];
        foreach($this->rules as $rule) {
            if(!$rule->matchMethod($_SERVER['REQUEST_METHOD']))
                continue;
            $vars = $rule->matchUrl($url);
            if($vars !== false) {
                $this->vars = $vars;
                $this->dynamic_controller = $rule->dynamic_controller;
                $this->dynamic_action = $rule->dynamic_action;
                $ex = '/\{([^\{\}]*)\}/';
                if($rule->dynamic_controller) {
                    $this->controller_name = preg_replace_callback(
                        $ex, [$this, "dynCallback"], $rule->controller_name);
                }
                else
                    $this->controller_name = $rule->controller_name;
                if($rule->dynamic_action) {
                    $this->action_name = preg_replace_callback(
                        $ex, [$this, "dynCallback"], $rule->action_name);
                }
                else
                    $this->action_name = $rule->action_name;
                if($this->findPath()) {
                    $this->found = true;
                    EQ::app()->trigger("route.found", $url);
                    return;
                }
            }
        }
        EQ::app()->trigger("route.notFound", $url);
    }

    protected function isFileModified($path, $url_prefix, $path_prefix)
    {
        $fdata = Cache::getValue("route.files", $path, []);
        if(!is_array($fdata) || !$fdata)
            return true;
        if(!isset($fdata[0], $fdata[1], $fdata[2], $fdata[3]))
            return true;
        if($fdata[0] !== $url_prefix || $fdata[1] !== $path_prefix)
            return true;
        if($fdata[2] !== filemtime($path))
            return true;
        if(!is_array($fdata[3]))
            return true;
        return false;
    }

    protected function findPath()
    {
        $cname = $this->findController();
        if(!$cname) {
            if($this->dynamic_controller)
                return false;
            else
                throw new RouteException("Controller not found: ".$this->controller_name);
        }
        $method = "action".Str::var2method($this->action_name);
        if(!method_exists($cname, $method)) {
            if($this->dynamic_action)
                return false;
            else
                throw new RouteException("Action method not found: $cname::$method");
        }
        $this->controller_class = $cname;
        $this->action_method = $method;
        return true;
    }

    protected function findController()
    {
        $parts = explode(".", $this->controller_name);
        if(count($parts) > 2 && $parts[0] === "modules") {
            array_shift($parts);
            $module = array_shift($parts);
            $ns = EQ::app()->module($module)->namespace."\\controllers\\";
            $cbasename = Str::var2method(array_pop($parts))."Controller";
            if($parts)
                $ns .= implode("\\", $parts)."\\";
            $cname = $ns.$cbasename;
            return Loader::classExists($cname) ? $cname : false;
        }
        else
            return Loader::autofindClass($this->controller_name, "controllers");
    }

    protected function dynCallback($m)
    {
        $vname = $m[1];
        if(!isset($this->vars[$m[1]]))
            throw new RouteException("Undefined variable in path: $vname");
        return $this->vars[$vname];
    }

}
