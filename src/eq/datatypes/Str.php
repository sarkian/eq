<?php

namespace eq\datatypes;

use eq\db\Schema;

class Str extends DataTypeBase
{

    public static function validate($value)
    {
        if(is_string($value) || is_int($value) || is_float($value))
            return true;
        return false;
    } 

    public static function filter($value)
    {
        if(self::validate($value))
            return (string) $value;
        return '';
    }

    public static function toDb($value)
    {
        return self::filter($value);
    }

    public static function fromDb($value)
    {
        return (string) $value;
    }

    public static function defaultValue()
    {
        return "";
    }

    public static function sqlType()
    {
        return Schema::TYPE_STRING;
    }

}
