<?php

namespace eq\helpers;

class Str
{

    const RS_CASE_LOW  = 0;
    const RS_CASE_UP   = 1;
    const RS_CASE_RAND = 2;

    public static function method2cmd($method, $lcfirst = true)
    {
        if($lcfirst) $method = lcfirst($method);
        $method = preg_replace_callback('/([A-Z])/', ['eq\helpers\Str', '_hypLower1'], $method);
        return $method;
    }

    public static function cmd2method($cmd, $ucfirst = true)
    {
        $cmd = preg_replace_callback('/\-([a-z])/', ['eq\helpers\Str', '_upper1'], $cmd);
        return $ucfirst ? ucfirst($cmd) : $cmd;
    }

    public static function method2var($method, $lcfirst = true)
    {
        if($lcfirst)
            $method = lcfirst($method);
        $method = preg_replace_callback('/[^a-zA-Z]([A-Z])/',
            ['eq\helpers\Str', '_lower0'], $method);
        $method = preg_replace_callback('/([A-Z])/', ['eq\helpers\Str', '_undLower1'], $method);
        return $method;
    }

    public static function var2method($cmd, $ucfirst = true)
    {
        $cmd = preg_replace_callback('/\_([a-z0-9])/', ['eq\helpers\Str', '_upper1'], $cmd);
        return $ucfirst ? ucfirst($cmd) : $cmd;
    }

    public static function cmdvar2method($cmd, $ucfirst = true)
    {
        $cmd = preg_replace_callback('/[_\-]([a-z])/', ['eq\helpers\Str', '_upper1'], $cmd);
        return $ucfirst ? ucfirst($cmd) : $cmd;
    }

    public static function cmd2var($cmd)
    {
        return str_replace("-", "_", $cmd);
    }

    public static function var2cmd($var)
    {
        return str_replace("_", "-", $var);
    }

    public static function method2label($method, $ucfirst = true, $ucfirst_all = true)
    {
        $label = preg_replace_callback('/[^\sa-zA-Z0-9]([a-zA-Z0-9])/',
            function($m) use($ucfirst_all) {
            return " ".($ucfirst_all ? ucfirst($m[1]) : $m[1]);
        }, $method);
        return $ucfirst ? ucfirst($label) : $label;
    }

    public static function className($class)
    {
        return is_object($class) ? get_class($class) : $class;
    }

    public static function classNamespace($class)
    {
        if(is_object($class))
            $class = get_class($class);
        $parts = explode("\\", $class);
        array_pop($parts);
        return implode("\\", $parts);

    }

    public static function classBasename($class)
    {
        if(is_object($class))
            $class = get_class($class);
        $parts = explode("\\", $class);
        return array_pop($parts);
    }

    public static function randstr($len = 16, $case = self::RS_CASE_LOW) {
        $str = '';
        for($i = 0; $i < $len; $i++) {
            $c = base_convert(rand(0, 35), 10, 36);
            if($case == self::RS_CASE_UP)
                $c = strtoupper($c);
            elseif($case == self::RS_CASE_RAND && rand(0, 1))
                $c = strtoupper($c);
            $str .= $c;
        }
        return $str;
    }

    protected static function _hypLower1($m)
    {
        return "-".strtolower($m[1]);
    }

    protected static function _upper1($m)
    {
        return strtoupper($m[1]);
    }

    protected static function _lower0($m)
    {
        return strtolower($m[0]);
    }

    protected static function _undLower1($m)
    {
        return "_".strtolower($m[1]);
    }

}
