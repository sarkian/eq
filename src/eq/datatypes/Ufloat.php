<?php

namespace eq\datatypes;

class Ufloat extends Float
{

    public static function validate($value)
    {
        if(is_float($value) && $value >= 0.0)
            return true;
        if(preg_match('/^[0-9]*(\.|)[0-9]*$/', $value))
            return true;
        return false;
    }

    public static function filter($value)
    {
        return abs((float) $value);
    }

    public static function toDb($value)
    {
        return abs((float) $value);
    }

    public static function fromDb($value)
    {
        return abs((float) $value);
    }

} 