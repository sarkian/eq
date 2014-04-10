<?php

namespace eq\web;

use EQ;
use eq\helpers\Arr;


class Route extends \eq\base\Object
{

    protected $_files = [];
    protected $_rules = [];
    protected $_found = false;
    protected $_controller = null;
    protected $_action = null;
    protected $_url_vars = [];
    protected $_found_rule = null;

    protected $created_urls = [];

    public function __construct($files)
    {
        foreach($files as $i => $fname) {
            $fname = EQ::getAlias($fname);
            if(isset($this->_files[$fname]))
                throw new RouteException("File already loaded: $fname");
            $this->_files[$fname] = new RouteFile($fname);
            $this->_rules = array_merge($this->_rules,
                $this->_files[$fname]->getRules());
        }
    }

    public function createUrl($path, $vars = [], $url_vars = [])
    {
        foreach($this->_rules as $rule) {
            $url = $rule->createUrl($path, $vars);
            if(!$url)
                continue;
            if($url_vars) {
                $url_vars_ = [];
                foreach($url_vars as $name => $value)
                    $url_vars_[] = $name."=".urlencode($value);
                $url .= "?".implode("&", $url_vars_);
            }
            return $url;
        }
        if(is_array($path))
            $path = implode(".", $path);
        throw new RouteException("Unable to create URL for path: $path");
    }

    public function processRequest()
    {
        $url = explode("?", $_SERVER['REQUEST_URI'])[0];
        foreach($this->_rules as $rule) {
            if(!$rule->matchMethod($_SERVER['REQUEST_METHOD']))
                continue;
            $vars = $rule->matchUrl($url);
            if($vars !== false) {
                $this->_url_vars = $vars;
                $this->_found = true;
                $this->_found_rule = $rule;
                break;
            }
        }
    }

    public function register($method, $url, $controller, $action = null)
    {
        $rule = new RouteRule();
        $rule->register($method, $url, $controller, $action);
        $this->_rules[] = $rule;
    }

    public function getFound()
    {
        return $this->_found;
    }

    public function getControllerInst()
    {
        return $this->_found_rule->controller_inst;
    }

    public function getControllerClass()
    {
        return $this->_found_rule->controller_class;
    }

    public function getController()
    {
        return $this->_found_rule->controller;
    }

    public function getAction()
    {
        return $this->_found_rule->action;
    }

    public function getControllerName()
    {
        return $this->_found_rule->controller_name;
    }

    public function getActionName()
    {
        return $this->_found_rule->action_name;
    }

    public function getVars()
    {
        return $this->_found_rule->vars;
    }

    public function getDynamicController()
    {
        return $this->_found_rule->dynamic_controller;
    }

    public function getDynamicAction()
    {
        return $this->_found_rule->dynamic_action;
    }

    public function getFoundRule()
    {
        return $this->_found_rule;
    }

}
