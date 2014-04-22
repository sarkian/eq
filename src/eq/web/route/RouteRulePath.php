<?php
/**
 * Last Change: 2014 Apr 22, 22:40
 */

namespace eq\web\route;

class RouteRulePath
{

    use \eq\base\TObject;

    protected $fname;
    protected $lnum;

    protected $prefix;

    protected $controller_name;
    protected $controller_class;
    protected $action_name;
    protected $action_method;

    protected $dynamic_controller = false;
    protected $dynamic_action = false;

    public function __construct($path, $fname, $lnum, $prefix = "")
    {
        $this->fname = $fname;
        $this->lnum = $lnum;
        $this->prefix = $prefix;
        $this->parse($path);
    }

    public function getControllerName()
    {
        return $this->controller_name;
    }

    public function getActionName()
    {
        return $this->action_name;
    }

    public function getDynamicController()
    {
        return $this->dynamic_controller;
    }

    public function getDynamicAction()
    {
        return $this->dynamic_action;
    }

    public function getDynamic()
    {
        return $this->dynamic_controller || $this->dynamic_action;
    }

    public function parse($path)
    {
        $this->preprocess($path);
    }

    protected function preprocess($path)
    {
        $parts = array_diff(explode(".", $path), [""]);
        if(count($parts) < 2)
            $this->except("Invalid path: $path");
        $parts = array_merge(array_diff(explode(".", $this->prefix), [""]), $parts);
        $this->action_name = array_pop($parts);
        $this->controller_name = implode(".", $parts);
        $this->dynamic_controller = (bool) preg_match("/\{[^\{\}]*\}/",
            $this->controller_name);
        $this->dynamic_action = (bool) preg_match("/\{[^\{\}]*\}/", $this->action_name);
    }

    protected function except($message)
    {
        throw new RouteSyntaxException($message, $this->fname, $this->lnum);
    }

}
