<?php

namespace eq\datatypes;

class Uint extends Int
{

    public static function validate($value)
    {
        if(is_int($value) && $value >= 0) return true;
        if(preg_match("/^[0-9]+$/", $value))
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
