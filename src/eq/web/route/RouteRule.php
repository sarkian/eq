<?php
/**
 * Last Change: 2014 Apr 24, 01:57
 */

namespace eq\web\route;

use eq\datatypes\DataTypeBase;

class RouteRule
{

    use \eq\base\TObject;

    protected $fname;
    protected $lnum;

    protected $url_prefix;
    protected $path_prefix;

    protected $method;
    protected $url;
    protected $path;

    protected $_url_reg = null;
    protected $_url_vars = null;
    protected $_controller_name = null;
    protected $_action_name = null;
    protected $_dynamic_controller = null;
    protected $_dynamic_action = null;
    protected $_url_mask = null;

    public function __construct($line = null, $fname = null, $lnum = null,
                $url_prefix = "", $path_prefix = "")
    {
        if($line)
            $this->parse($line, $fname, $lnum, $url_prefix, $path_prefix);
    }

    public function getUrlReg()
    {
        return is_null($this->_url_reg) ? $this->url->reg :  $this->_url_reg;
    }

    public function getUrlVars()
    {
        return is_null($this->_url_vars) ? $this->url->vars : $this->_url_vars;
    }

    public function getControllerName()
    {
        return is_null($this->_controller_name)
            ? $this->path->controller_name : $this->_controller_name;
    }

    public function getActionName()
    {
        return is_null($this->_action_name)
            ? $this->path->action_name : $this->_action_name;
    }

    public function getDynamicController()
    {
        return is_null($this->_dynamic_controller)
            ? $this->path->dynamic_controller : $this->_dynamic_controller;
    }

    public function getDynamicAction()
    {
        return is_null($this->_dynamic_action)
            ? $this->path->dynamic_action : $this->_dynamic_action;
    }

    public function getUrlMask()
    {
        return is_null($this->_url_mask) ? $this->url->mask : $this->_url_mask;
    }

    public function getPathname()
    {
        return $this->controller_name.".".$this->action_name;
    }

    public function setMethod($method_)
    {
        $method = strtoupper(trim($method_, " \r\n\t"));
        if(!in_array($method, [
            "*",
            "GET",
            "POST",
            "PUT",
            "PATCH",
            "DELETE",
        ], true))
            $this->except("Invalid method: $method_");
        $this->method = $method;
    }

    public function saveData()
    {
        $data = [
            'method' => $this->method,
            'url_reg' => $this->url_reg,
            'url_vars' => $this->url_vars,
            'url_mask' => $this->url_mask,
            'controller_name' => $this->controller_name,
            'action_name' => $this->action_name,
            'dynamic_controller' => $this->dynamic_controller,
            'dynamic_action' => $this->dynamic_action,
        ];
        return $data;
    }

    public function loadData($data)
    {
        $this->method = $data['method'];
        $this->_url_reg  = $data['url_reg'];
        $this->_url_vars = $data['url_vars'];
        $this->_url_mask = $data['url_mask'];
        $this->_controller_name = $data['controller_name'];
        $this->_action_name = $data['action_name'];
        $this->_dynamic_controller = $data['dynamic_controller'];
        $this->_dynamic_action = $data['dynamic_action'];
    }

    public function createUrl($path, $vars = [])
    {
        $parts = array_merge(array_diff(preg_split("/[\\\.]/", $this->pathname), [""]));
        $parts_p = array_merge(array_diff(preg_split("/[\\\.]/", $path), [""]));
        if(count($parts) !== count($parts_p))
            return false;
        foreach($parts as $i => $part) {
            if(preg_match_all("/\{([^\{\}]*)\}/", $part, $matches)) {
                foreach($matches[1] as $var) {
                    $val = $parts_p[$i];
                    $vars[$var] = $val;
                }
            }
            elseif($part !== $parts_p[$i])
                return false;
        }
        $url = [];
        foreach($this->url_mask as $part) {
            if($part[0]) {
                $name = $part[1];
                if(!isset($vars[$name]))
                    throw new RouteException("Missed variable: $name");
                $url[] = $vars[$name];
            }
            else
                $url[] = $part[1];
        }
        return implode("", $url);
    }

    public function parse($line, $fname, $lnum, $url_prefix = "", $path_prefix = "")
    {
        $this->fname = $fname;
        $this->lnum = $lnum;
        $this->url_prefix = $url_prefix;
        $this->path_prefix = $path_prefix;
        $parts = preg_split("/[\s\t]+/", $line);
        if(count($parts) !== 3)
            $this->except("Invalid rule");
        $this->setMethod($parts[0]);
        $this->url = new RouteRuleUrl($parts[1],
            $this->fname, $this->lnum, $this->url_prefix);
        $this->path = new RouteRulePath($parts[2],
            $this->fname, $this->lnum, $this->path_prefix);
        $this->path->validateVariables($this->url->vars);
    }

    public function matchMethod($method)
    {
        return $this->method === "*" ? true : $this->method === $method;
    }

    public function matchUrl($url)
    {
        if(!preg_match($this->url_reg, $url, $matches))
            return false;
        $i = 1;
        $vars = [];
        foreach($this->url_vars as $var => $type) {
            if(!isset($matches[$i]))
                $this->exceptParsing("URL variable not found: $var");
            $type = DataTypeBase::getClass($type);
            $vars[$var] = $type::filter($matches[$i]);
            $i++;
        }
        return $vars;
    }

    protected function except($message)
    {
        throw new RouteSyntaxException($message, $this->fname, $this->lnum);
    }

    protected function exceptParsing($message)
    {
        throw new RouteParsingException($message, $this->fname, $this->lnum);
    }

    protected function unexpected($unexp, $exp)
    {
        if(is_array($exp)) {
            $exp_ = [];
            foreach($exp as $str)
                $exp_[] = count(preg_split("/\s|_/", $str)) == 1
                    ? "'$str'" : $str;;
            $exp = implode(" or ", $exp_);
        }
        elseif(count(preg_split("/\s|_/", $exp)) < 2)
            $exp = "'$exp'";
        if(count(preg_split("/\s|_/", $unexp)) == 1)
            $unexp = "'$unexp'";
        throw new RouteSyntaxException(
            "Unexpected $unexp, expecting $exp", $this->fname, $this->lnum);
    }

}
