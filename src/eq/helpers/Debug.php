<?php

namespace eq\helpers;


use EQ;
use eq\base\Loader;

class Debug
{

    private static $_obj_info = [];

    public static function callLocation($skip = 1, $absolute = false)
    {
        $trace = debug_backtrace();
        $file = "UNKNOWN";
        $line = 0;
        if(isset($trace[$skip]['file'])) {
            $file = $trace[$skip]['file'];
            $line = $trace[$skip]['line'];
        } else {
            foreach(array_reverse($trace) as $call) {
                if(isset($call['file'])) {
                    $file = $call['file'];
                    $line = $call['line'];
                    break;
                }
            }
        }
        if(!$absolute)
            $file = EQ::unalias($file);
        return [$file, $line];
    }

    public static function shortDump($var, $opts = [])
    {
        $default_func = function($s) { return $s; };
        $opts = Arr::extend($opts, [
            'full' => false,
            'limit' => 64,
            'typename_wrapfunc' => $default_func,   // bool, int, object, etc.
            'operator_wrapfunc' => $default_func,   // brackets, commas
            'keyword_wrapfunc' => $default_func,    // true, false, null
            'number_wrapfunc' => $default_func,     // numbers (integer and float)
            'string_wrapfunc' => $default_func,     // strings
            'classname_wrapfunc' => $default_func,  // class name
            'id_wrapfunc' => $default_func,         // object id, stream id (without braces)
            'refcount_wrapfunc' => $default_func,   // reference count (without braces)
        ]);
        $typename = $opts['typename_wrapfunc'];
        $operator = $opts['operator_wrapfunc'];
        $keyword = $opts['keyword_wrapfunc'];
        $number = $opts['number_wrapfunc'];
        $string = $opts['string_wrapfunc'];
        $classname = $opts['classname_wrapfunc'];
        $id = $opts['id_wrapfunc'];
        $refcount = $opts['refcount_wrapfunc'];
        $limit = $opts['limit'];
        $str = "";
        switch(gettype($var)) {
            case "boolean":
                $str = $typename("bool").$operator("(")
                    .$keyword($var ? "true" : "false").$operator(")");
                break;
            case "integer":
                $str = $typename("int").$operator("(").$number($var).$operator(")");
                break;
            case "double":
                $str = $typename("float").$operator("(").$number($var).$operator(")");
                break;
            case "string":
                $cn = "";
                if(!$opts['full'] && strlen($var) + 10 > $limit) {
                    $var = substr($var, 0, $limit - 13);
                    $cn = "...";
                }
                $str = $typename("string").$operator("(").$string($var, $cn).$operator(")");
                break;
            case "array":
                if($opts['full']) {
                    // TODO: Implement
                }
                else {
                    $is_cls = isset($var[0]) && (is_object($var[0])
                            || (is_string($var[0]) && strlen($var[0])
                                && Loader::classExists($var[0])));
                    if($is_cls && is_callable($var)) {
                        if(is_object($var[0])) {
                            $cls_len = strlen(get_class($var[0])) + 9;
//                                + strlen((string) self::getObjectId($var[0]));
                            $cls_str = $typename("object").$operator("(")
                                .$classname(get_class($var[0]))
                                .$operator(")");
//                                .$id("#".self::getObjectId($var[0]));
                        }
                        else {
                            $cls_len = strlen($var[0]);
                            $cls_str = $classname($var[0]);
                        }
                        if($cls_len + 4 + strlen($var[1]) <= $limit) {
                            $str = $operator("[").$cls_str.$operator(", ")
                                .$string($var[1]).$operator("]");
                        }
                    }
                    if(!$str)
                        $str = $typename("array").$operator("(")
                            .$number(count($var)).$operator(")");
                }
                break;
            case "object":
                $str = $typename("object").$operator("(").$classname(get_class($var))
                    .$operator(")");
//                    .$id("#".self::getObjectId($var))
//                    .$operator("(").$refcount(self::getObjectRefcount($var)).$operator(")");
                if(isset(class_implements($var)['Countable']))
                    $str .= $operator("(").$number(count($var)).$operator(")");
                break;
            case "resource":
                $str = $typename("resource").$operator("(").$number((int) $var).$operator(")[")
                    .$classname(get_resource_type($var)).$operator("]");
                break;
            case "NULL":
                $str = $keyword("null");
        }
        return $str;
    }

    public static function getObjectId($obj)
    {
        return self::getObjectInfo($obj)['id'];
    }

    public static function getObjectRefcount($obj)
    {
        return self::getObjectInfo($obj)['refcount'];
    }

    public static function getObjectInfo($obj)
    {
        // TODO: Fix or remove!
        $hash = spl_object_hash($obj);
        if(!isset(self::$_obj_info[$hash])) {
            ob_start();
            var_dump($obj);
            $dump = ob_get_clean();
            if(preg_match('/#([0-9]+) \(([0-9]+)\)/', $dump, $matches))
                self::$_obj_info[$hash] = [
                    'id' => (int) $matches[1],
                    'refcount' => (int) $matches[2],
                ];
            else
                self::$_obj_info[$hash] = [
                    'id' => 0,
                    'refcount' => 0,
                ];
        }
        return self::$_obj_info[$hash];
    }

}