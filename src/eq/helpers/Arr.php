<?php
/**
 * Last Change: 2014 Mar 16, 14:40
 */

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

    protected static function keystr($key)
    {
        return is_string($key) ? $key : implode(".", $key);
    }

    protected static function keyarr($key)
    {
        return is_array($key) ? $key : array_diff(explode(".", $key), [""]);
    }

}
