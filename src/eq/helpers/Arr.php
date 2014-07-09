<?php

namespace eq\helpers;

use eq\base\InvalidCallException;

class Arr
{

    public static function extend($input, $default)
    {
        foreach($default as $name => $value)
            isset($input[$name]) or $input[$name] = $value;
        return $input;
    }

    public static function merge($one, $two)
    {
        is_array($one) or $one = [];
        is_array($two) or $two = [];
        foreach($two as $key => $val) {
            if(is_string($key)) {
                if(is_array($val))
                    $one[$key] = self::merge(
                        isset($one[$key]) ? $one[$key] : [], $val);
                else
                    $one[$key] = $val;
            }
            elseif(!in_array($val, $one))
                $one[] = $val;
        }
        return $one;
    }

    public static function getItem($src, $key = null, $default = null)
    {
        if(!$key)
            return $src;
        $val = $src;
        $keys = self::keyarr($key);
        if(!$keys)
            throw new InvalidCallException("Invalid key: ".self::keystr($key));
        foreach($keys as $k) {
            if(is_array($val) && isset($val[$k]))
                $val = $val[$k];
            else
                return $default;
        }
        return $val;
    }

    public static function setItem(&$src, $key, $value, $unique = false)
    {
        $val = &$src;
        $keys = self::keyarr($key);
        if(!$keys)
            throw new InvalidCallException("Invalid key: ".self::keystr($key));
        $lastkey = array_pop($keys);
        if(substr($lastkey, -2) === "[]") {
            $lastkey = substr($lastkey, 0, -2);
            $la = true;
        }
        else
            $la = false;
        array_push($keys, $lastkey);
        foreach($keys as $k) {
            if(!isset($val[$k]) || !is_array($val[$k]))
                $val[$k] = [];
            $val = &$val[$k];
        }
        if($la)
            $val[] = $value;
        else
            $val = $value;
        if($unique) {
            if($la)
                $val = array_unique($val);
            else
                $src = array_unique($src);
        }
    }

    public static function unsetItem(&$src, $key)
    {
        $val = &$src;
        $keys = self::keyarr($key);
        if(!$keys)
            throw new InvalidCallException("Invalid key: ".self::keystr($key));
        $lastkey = array_pop($keys);
        foreach($keys as $k) {
            if(!isset($val[$k]) || !is_array($val[$k]))
                return;
            $val = &$val[$k];
        }
        unset($val[$lastkey]);
    }

    public static function itemExists($src, $key)
    {
        $keys = self::keyarr($key);
        $val = $src;
        if(!$keys)
            throw new InvalidCallException("Invalid key: ".self::keystr($key));
        $lastkey = array_pop($keys);
        foreach($keys as $k) {
            if(!isset($val[$k]) || !is_array($val[$k]))
                return false;
            $val = $val[$k];
        }
        return isset($val[$lastkey]);
    }

    protected static function keystr($key)
    {
        return is_string($key) ? $key : implode(".", $key);
    }

    protected static function keyarr($key)
    {
        return is_array($key) ? $key : array_diff(explode(".", $key), [""]);
    }

}
