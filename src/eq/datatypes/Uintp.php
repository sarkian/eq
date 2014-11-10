<?php

namespace eq\datatypes;

class Uintp extends Uint
{

    public static function validate($value)
    {
        if(is_int($value))
            return $value > 0;
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
