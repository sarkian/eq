<?php
/**
 * Last Change: 2014 Apr 10, 12:29
 */

namespace eq\web;

use EQ;
use eq\datatypes\DataTypeBase;
use eq\helpers\Dbg;
use eq\helpers\Str;
use eq\base\Loader;

class RouteRule extends \eq\base\Object
{

    protected $fname;
    protected $lnum;

    protected $_method;
    protected $_vars = [];
    protected $_path;
    protected $_path_vars = [];
    protected $_controller_inst = null;
    protected $_controller_class = null;
    protected $_controller;
    protected $_action;
    protected $_controller_name;
    protected $_action_name;
    protected $_id;

    protected $_dynamic_controller = false;
    protected $_dynamic_action = false;

    protected $url_regexp = "/^.*$/";
    protected $url_vars = [];

    protected $preprocessed = false;
    protected $generated_rules = [];

    protected $create_url_mask = "";

    public static function fromString($line, $fname, $lnum)
    {
        $rule = new RouteRule();
        $rule->parse($line, $fname, $lnum);
        return $rule;
    }

    public function register($method, $url, $controller, $action = null)
    {
        $this->validateMethod($method);
        $this->_method = $method;
        $action or $action = "{action}";
        if(is_object($controller)) {
            $cname = get_class($controller);
            $this->_controller_inst = $controller;
        }
        elseif(is_string($controller)) {
            if(!Loader::classExists($controller))
                throw new RouteException("Controller not found: $controller");
            $cname = $controller;
            $this->_controller_class = $controller;
        }
        else
            throw new RouteException("Invalid controller");
        $this->_path = str_replace("\\", ".", "$cname.$action");
        $this->preprocessPath();
        $this->processUrl($url);
    }

    public function createUrl($path, $vars = [])
    {
        if(is_array($path))
            $path = implode(".", $path);
        if(!$this->create_url_mask)
            return false;
        $parts = array_merge(array_diff(
            preg_split("/[\\\.]/", $this->_path), [""]));
        $parts_p = array_merge(array_diff(
            preg_split("/[\\\.]/", $path), [""]));
        if(count($parts) !== count($parts_p))
            return false;
        foreach($parts as $i => $part) {
            if(preg_match_all("/\{([^\{\}]*)\}/", $part, $matches)) {
                foreach($matches[1] as $var) {
                    if(!$var)
                        $this->unexpected("end of path", "variable name");
                    $val = $parts_p[$i];
                    $vars[$var] = $val;
                }
            }
            elseif($part !== $parts_p[$i])
                return false;
        }
        $url = $this->create_url_mask;
        if(preg_match_all("/__TT_VAR_([a-z_]+)__/", $url, $matches)) {
            foreach($matches[1] as $name) {
                if(!isset($vars[$name]))
                    throw new RouteException("Missing path variable: $name");
                $url = str_replace("__TT_VAR_{$name}__", $vars[$name], $url);
            }
        }
        return $url;
    }

    public function parse($line, $fname, $lnum)
    {
        $this->preprocess($line);
        if($this->preprocessed)
            return;
        $this->fname = $fname;
        $this->lnum = $lnum;
        $parts = preg_split("/\s+/", $line, 4);
        if(count($parts) < 3)
            $this->except("Unexpected rule end");
        $id = null;
        if(count($parts) == 3)
            list($method, $url, $path) = $parts;
        else
            list($method, $url, $path, $id) = $parts;
        if(!strncmp($id, "#", 1))
            $id = null;
        $this->validateMethod($method);
        $this->_method = $method;
        $this->_path = $path;
        $this->preprocessPath();
        $this->processUrl($url);
        // $this->processPath();
        $this->_id = $id;
    }

    public function preprocess($line)
    {
        if(!preg_match("/\{\{[^\{\}]+\}\}/", $line, $matches))
            return;
        $controllers = [];
        foreach($matches as $match) {
            $controllers[] = trim(substr($match, 2, -2));
        }
        $controllers = array_unique($controllers);
        if(!$controllers || count($controllers) > 1)
            $this->except("Invalid preprocessor directive");
        $cbasename = array_pop($controllers);
        $cname = Loader::autofindClass($cbasename, "controllers");
        if(!$cname)
            throw new RouteException(
                "Unknown controller: $cbasename", $this->fname, $this->lnum);
        $this->preprocessed = true;
        // print_r($cname::routes($line));
        // exit;
        $routes = $cname::routes($line);
        if(!is_array($routes))
            $routes = [$routes];
        foreach($routes as $rule)
            $this->generated_rules[] = self::fromString(
                $rule, $this->fname, $this->lnum);
    }

    public function matchUrl($url)
    {
        if(!preg_match($this->url_regexp, $url, $matches))
            return false;
        $i = 1;
        foreach($this->url_vars as $var => $type) {
            if(!isset($matches[$i]))
                $this->exceptParsing("URL variable submask not found: $var");
            $type = DataTypeBase::getClass($type);
            $this->_vars[$var] = $type::filter($matches[$i]);
            $i++;
        }
        $this->processPath();
        return true;
    }

    public function matchMethod($method)
    {
        return $this->_method === "*" ? true : $this->_method === $method;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getControllerInst()
    {
        return $this->_controller_inst;
    }

    public function getControllerClass()
    {
        return $this->_controller_class;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function getControllerName()
    {
        return $this->_controller_name;
    }

    public function getActionName()
    {
        return $this->_action_name;
    }

    public function getVars()
    {
        return $this->_vars;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getDynamicController()
    {
        return $this->_dynamic_controller;
    }

    public function getDynamicAction()
    {
        return $this->_dynamic_action;
    }

    public function getFile()
    {
        return $this->fname;
    }

    public function getLine()
    {
        return $this->lnum;
    }

    public function getPreprocessed()
    {
        return $this->preprocessed;
    }

    public function getGeneratedRules()
    {
        return $this->generated_rules;
    }

    protected function preprocessPath()
    {
        if(!preg_match_all("/\{([^\{\}]*)\}/", $this->_path, $matches))
            return;
        foreach($matches[1] as $var) {
            if(!$var)
                $this->unexpected("}", "variable name");
            $this->_path_vars[] = $var;
        }
    }

    protected function processPath()
    {
        $path = array_merge(array_diff(explode(".", $this->_path), [""]));
        if(count($path) < 2)
            $this->except("Invalid path: {$this->_path}");
        array_walk($path, function(&$part, $i) use($path) {
            if(preg_match_all("/\{([^\{\}]*)\}/", $part, $matches)) {
                foreach($matches[1] as $var) {
                    if(!$var)
                        $this->unexpected("end of path", "variable name");
                    if(!isset($this->_vars[$var]))
                        $this->except("Undefined variable in path: $var");
                    $val = (string) $this->_vars[$var];
                    if(!strlen($val))
                        $this->except("Can't use value in path: "
                            .Dbg::dump($this->_vars[$var]));
                    $part = str_replace("{".$var."}", $val, $part);
                    if($i == count($path) - 1)
                        $this->_dynamic_action = true;
                    else
                        $this->_dynamic_controller = true;
                }
            }
        });
        $this->_action_name = array_pop($path);
        $this->_controller_name = implode(".", $path);
        $this->_action = "action".Str::cmd2method($this->_action_name);
        $cname = Str::cmd2method(array_pop($path))."Controller";
        $path[] = $cname;
        $this->_controller = implode("\\", $path);
    }

    protected function processUrl($url_src)
    {
        if(strncmp($url_src, "/", 1))
            $this->unexpected(substr($url, 0, 1), "/");
        list($url, $vars_t, $regs_t) = $this->preprocessUrl($url_src);
        $vars = [];
        $regs = [];
        $n = 0;
        foreach($vars_t as $var) {
            $v_token = "__T_VAR{$var}__";
            if(strstr($url_src, $v_token))
                $this->except("Reserved word in URL: $v_token");
            if(!preg_match("/$v_token(.*)$v_token/", $url, $matches))
                $this->exceptParsing("URL variable not found: $var");
            list($var_full, $var_body) = $matches;
            if(!$var_body)
                $this->unexpected("}", "variable name");
            $var_name = null;
            $var_reg = null;
            foreach($regs_t as $i => $reg) {
                $r_token = "__T_RE{$reg}__";
                if(!preg_match("/$r_token(.*)$r_token/" , $var_body, $matches))
                    continue;
                unset($regs_t[$i]);
                list($reg_full, $reg_body) = $matches;
                $parts = array_merge(array_diff(
                    explode($reg_full, $var_body), [""]));
                if(!$parts)
                    $this->unexpected("regular expression", "variable name");
                $var_name = $parts[0];
                if(count($parts) > 1)
                    $this->except("Trailing characters in variable: $var_name");
                $var_reg = $reg_body;
            }
            if(!$var_name) {
                $var_name = $var_body;
                // $var_reg = in_array($var_name, $this->_path_vars)
                        // ? "[a-z\-_]+" : ".+";
                $var_reg = null;
            }
            $var_name_parts = explode(":", $var_name);
            if(count($var_name_parts) > 2)
                $this->except("Invalid variable name: $var_name");
            $var_name = $var_name_parts[0];
            $var_type = isset($var_name_parts[1])
                ? $var_name_parts[1] : "str";
            if(!$var_reg) {
                $type_class = DataTypeBase::getClass($var_type);
                $var_reg = $type_class::pattern();
            }
            if(!preg_match("/^[a-zA-Z_][a-zA-Z0-9_]*$/", $var_name))
                $this->except("Invalid variable name: $var_name");
            if(!preg_match("/^[a-zA-Z_][a-zA-Z0-9_]*$/", $var_type))
                $this->except("Invalid variable type: $var_type ($var_name)");
            if(isset($vars[$var_name]))
                $this->except("Duplicate variable: $var_name");
            $vars[$var_name] = [
                'num' => $n,
                'name' => $var_name,
                'type' => $var_type,
                'reg' => $var_reg,
            ];
            $url = str_replace($var_full, "__TT_VAR{$n}__", $url);
            $n++;
        }
        $n = 0;
        foreach($regs_t as $reg) {
            $r_token = "__T_RE{$reg}__";
            if(strstr($url_src, $r_token))
                $this->except("Reserved word in URL: $r_token");
            if(!preg_match("/$r_token(.*)$r_token/", $url, $matches))
                $this->exceptParsing("Regexp not found: $reg");
            list($reg_full, $reg_body) = $matches;
            if(!$reg_body)
                $this->unexpected(">", "regular expression");
            $regs[] = [
                'num' => $n,
                'reg' => $reg_body,
            ];
            $url = str_replace($reg_full, "__TT_REG{$n}__", $url);
            $n++;
        }
        $this->postprocessUrl($url, $vars, $regs);
    }

    protected function preprocessUrl($url)
    {
        $f_re = 0;
        $f_var = 0;
        $f_esc = [
            '<' => 0,
            '>' => 0,
        ];
        $vars = [];
        $regs = [];
        $i = 1;
        $url = preg_replace_callback("/\{|\}|\\\{|\\\}|<|>|\\\<|\\\>/",
            function($matches)
                    use(&$f_re, &$f_var, &$f_esc, &$vars, &$regs, &$i) {
                $match = $matches[0];
                switch($match) {
                case '{':
                    if(!$f_var && !$f_re) {
                        $f_var = $i;
                        $res = "__T_VAR{$i}__";
                    }
                    else $res = "{";
                    break;
                case '}':
                    if($f_var && !$f_re) {
                        $res = "__T_VAR{$f_var}__";
                        $vars[] = $f_var;
                        $f_var = 0;
                    }
                    else $res = "}";
                    break;
                case '\{':
                    $res = $f_re ? "\{" : "{";
                    break;
                case '\}':
                    $res = $f_re ? "\}" : "}";
                    break;
                case '<':
                    // if($f_esc['<']) {
                        // $f_esc['<'] = 0;
                        // $res = "";
                    // }
                    if(!$f_re) {
                        $f_re = $i;
                        $res = "__T_RE{$i}__";
                    }
                    else
                        $this->unexpected("<", "\<");
                    break;
                case '>':
                    // if($f_esc['>']) {
                        // $f_esc['>'] = 0;
                        // $res = "";
                    // }
                    if($f_re) {
                        $res = "__T_RE{$f_re}__";
                        $regs[] = $f_re;
                        $f_re = 0;
                    }
                    else
                        $this->unexpected(">", "\>");
                    break;
                case '\<':
                    // $f_esc['<'] = 1;
                    $res = "<";
                    break;
                case '\>':
                    // $f_esc['>'] = 1;
                    $res = ">";
                    break;
                default:
                    $res = $match;
                }
                $i++;
                return $res;
            }, $url);
        if($f_re)
            $this->unexpected("end of URL", ">");
        if($f_var)
            $this->unexpected("end of URL", "}");
        return [$url, $vars, $regs];
    }

    protected function postprocessUrl($url, $vars, $regs)
    {
        if(!$regs)
            $this->create_url_mask = $url;
        $url = preg_quote($url, "/");
        foreach($vars as $var) {
            if($this->create_url_mask)
                $this->create_url_mask = str_replace(
                    "__TT_VAR{$var['num']}__", "__TT_VAR_{$var['name']}__",
                    $this->create_url_mask);
            $url = str_replace(
                "__TT_VAR{$var['num']}__", "({$var['reg']})", $url);
            $this->url_vars[$var['name']] = $var['type'];
        }
        foreach($regs as $reg) {
            $url = str_replace("__TT_REG{$reg['num']}__", $reg['reg'], $url);
        }
        $this->url_regexp = "/^$url$/";
    }

    protected function validateMethod($method)
    {
        if(!in_array($method, [
            "*",
            "GET",
            "POST",
            "PUT",
            "PATCH",
            "DELETE",
        ]))
            $this->except("Invalid method: $method");
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
