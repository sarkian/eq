<?php

namespace eq\web\route;

use eq\base\TObject;
use eq\datatypes\DataTypeBase;

/**
 * @property string reg
 * @property array vars
 * @property array mask
 */
class RouteRuleUrl
{

    use TObject;

    protected $fname;
    protected $lnum;

    protected $prefix;

    protected $flags = [];
    protected $tokens = [];
    protected $token = null;

    protected $reg = "";
    protected $vars = [];
    protected $mask = [];

    public function __construct($url, $fname = null, $lnum = null, $prefix = "")
    {
        $this->fname = $fname;
        $this->lnum = $lnum;
        $this->prefix = $prefix;
        $this->parse("/".ltrim($url, "/"));
    }

    public function getReg()
    {
        return $this->reg;
    }

    public function getVars()
    {
        return $this->vars;
    }

    public function getMask()
    {
        return $this->mask;
    }

    public function parse($url)
    {
        $url = "/".trim($url, " \r\n\t/");
        $chars = str_split($url);
        foreach($chars as $ch) {
            if($this->flag("var"))
                $this->processCharVar($ch);
            elseif($this->flag("reg"))
                $this->processCharReg($ch);
            else
                $this->processChar($ch);
        }
        $this->tokenEnd();
        if($this->flag("var"))
            $this->except("Unterminated variable");
        if($this->flag("reg"))
            $this->except("Unterminated regexp");
        $this->processTokens();
    }

    protected function processChar($ch)
    {
        if($ch === "{") {
            $this->tokenStart("var_start");
            $this->flag("var", 1);
        }
        elseif($ch === "<") {
            $this->tokenStart("reg");
            $this->flag("reg", 1);
        }
        else {
            if(is_null($this->token))
                $this->tokenStart("str");
            $this->tokenPush($ch);
        }
    }

    protected function processCharVar($ch)
    {
        if($this->flag("reg")) {
            $this->processCharReg($ch);
        }
        elseif($ch === "<") {
            $this->tokenStart("reg");
            $this->flag("reg", 1);
        }
        elseif($ch === "}") {
            $this->tokenEnd();
            $this->tokenAdd("var_end");
            $this->flag("var", 0);
        }
        else {
            $this->tokenPush($ch);
        }
    }

    protected function processCharReg($ch)
    {
        if($this->flag("esc")) {
            if($ch !== "<" && $ch !== ">")
                $this->tokenPush("\\");
            $this->tokenPush($ch);
            $this->flag("esc", 0);
        }
        elseif($ch === "\\") {
            $this->flag("esc", 1);
        }
        elseif($ch === ">") {
            $this->tokenEnd();
            $this->flag("reg", 0);
        }
        else {
            $this->tokenPush($ch);
        }
    }

    protected function tokenStart($type)
    {
        if(!is_null($this->token))
            $this->tokens[] = $this->token;
        $this->token = [
            'type' => $type,
            'str' => "",
        ];
    }

    protected function tokenPush($ch)
    {
        if(is_null($this->token))
            $this->tokenStart("UNKNOWN");
        $this->token['str'] .= $ch;
    }

    protected function tokenEnd()
    {
        if(!is_null($this->token))
            $this->tokens[] = $this->token;
        $this->token = null;
    }

    protected function tokenAdd($type, $str = "")
    {
        if(!is_null($this->token))
            $this->tokens[] = $this->token;
        $this->token = null;
        $this->tokens[] = [
            'type' => $type,
            'str' => $str,
        ];
    }

    protected function processTokens()
    {
        $this->reg = "/^".preg_quote($this->prefix, "/");
        $this->mask[] = [false, $this->prefix];
        $this->vars = [];
        $var_reg = "";
        $var_type = "str";
        foreach($this->tokens as $token) {
            $type = $token['type'];
            $str = $token['str'];
            if($type === "str") {
                $this->reg .= preg_quote($str, "/");
                $this->mask[] = [false, $str];
            }
            elseif($type === "reg") {
                if($this->flag("var"))
                    $var_reg .= $str;
                else
                    $this->reg .= $str;
            }
            elseif($type === "var_start") {
                $this->flag("var", 1);
                $var_reg = "";
                $parts = explode(":", $str);
                if(count($parts) < 1 || count($parts) > 2)
                    $this->except("Invalid variable: $str");
                $var_name = $parts[0];
                $var_type = isset($parts[1]) ? $parts[1] : "str";
                if(isset($this->vars[$var_name]))
                    $this->except("Variable already used: $var_name");
                $this->vars[$var_name] = $var_type;
                $this->mask[] = [true, $var_name];
            }
            elseif($type === "var_end") {
                $this->flag("var", 0);
                if(!$var_reg) {
                    $type_class = DataTypeBase::getClass($var_type);
                    $var_reg = $type_class::pattern();
                }
                $this->reg .= "(".$var_reg.")";
            }
            else {
                $this->except("Unknown token: $str");
            }
        }
        $this->reg .= '\/{0,1}$/';
    }

    protected function flag($name, $value = null)
    {
        if(is_null($value))
            return isset($this->flags[$name]) ? $this->flags[$name] : false;
        else
            return $this->flags[$name] = (bool) $value;
    }

    protected function except($message)
    {
        throw new RouteSyntaxException($message, $this->fname, $this->lnum);
    }

}
