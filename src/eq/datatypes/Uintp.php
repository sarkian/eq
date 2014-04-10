<?php
/**
 * Last Change: 2014 Mar 15, 20:39
 */

namespace eq\datatypes;

class Uintp extends Uint
{

    public static function validate($value)
    {
        if(is_int($value) && $value > 0) return true;
        if(preg_match("/^[1-9][0-9]*$/", $value))
            return true;
        return false;
    }

    public static function filter($value)
    {
        return abs((int) $value);
    }

    public static function toDb($value)
    {
        return abs((int) $value);
    }

    public static function fromDb($value)
    {
        return abs((int) $value);
    }

}
