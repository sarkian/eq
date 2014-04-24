<?php
/**
 * Last Change: 2014 Apr 24, 03:57
 */

namespace eq\web\route;

use EQ;
use eq\helpers\FileSystem;
use eq\helpers\Str;
use eq\base\Loader;

class Route
{

    use \eq\base\TObject;

    protected $files = [];
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
        foreach(EQ::app()->route_files as $fname => $fdata) {
            $fname = EQ::getAlias($fname);
            if(!is_array($fdata))
                $fdata = [];
            $url_prefix = isset($fdata[0]) ? $fdata[0] : "";
            $path_prefix = isset($fdata[1]) ? $fdata[1] : "";
            $this->files[$fname] = [
                $url_prefix,
                $path_prefix,
                filemtime($fname),
            ];
        }
        if($this->isModified()) {
            foreach($this->files as $fname => $fdata) {
                $file = new RouteFile($fname, $fdata[0], $fdata[1]);
                $this->rules = array_merge($this->rules, $file->rules);
            }
            EQ::cache("route.files", $this->files);
            $rcache = [];
            foreach($this->rules as $rule)
                $rcache[] = $rule->saveData();
            EQ::cache("route.rules", $rcache);
        }
        else {
            foreach(EQ::cache("route.rules") as $data) {
                $rule = new RouteRule();
                $rule->loadData($data);
                $this->rules[] = $rule;
            }
        }
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
                foreach($url_vars as $name => $value)
                    $uvars[] = $name."=".urlencode($value);
                $url .= "?".implode("&", $uvars);
            }
            return $url;
        }
        // throw new RouteException("Unable to create URL for path: $path");
    }

    public function processRequest()
    {
        $url = explode("?", $_SERVER['REQUEST_URI'])[0];
        foreach($this->rules as $rule) {
            if(!$rule->matchMethod($_SERVER['REQUEST_METHOD']))
                continue;
            $vars = $rule->matchUrl($url);
            if($vars !== false) {
                $this->vars = $vars;
                $this->dynamic_controller = $rule->dynamic_controller;
                $this->dynamic_action = $rule->dynamic_action;
                $ex = "/\{([^\{\}]*)\}/";
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
                    return;
                }
            }
        }
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
        // modules.user.user.login
        // eq\modules\user\controllers\UserController
        $parts = explode(".", $this->controller_name);
        if($parts[0] === "modules") {
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

    protected function isModified()
    {
        $cache = EQ::cache("route.files");
        foreach($this->files as $fname => $fdata) {
            if(!isset($cache[$fname]) || $cache[$fname] !== $fdata)
                return true;
            unset($cache[$fname]);
        }
        return $cache ? true : false;
    }

}
