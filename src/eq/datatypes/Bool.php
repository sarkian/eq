<?php

namespace eq\datatypes;

use eq\db\mysql\Schema;

class Bool extends DataTypeBase
{

    protected static $true_variants = ['1', 'true', 'on', 'yes'];
    protected static $false_variants = ['0', 'false', 'off', 'no'];

    public static function validate($value)
    {
        if(is_bool($value))
            return true;
        if(is_string($value)) {
            if(!strlen($value))
                return true;
            if(in_array(strtolower($value), array_merge(self::$true_variants, self::$false_variants), true))
                return true;
            return false;
        }
        if(is_int($value) && ($value === 1 || $value === 0))
            return true;
        if(is_float($value) && ($value === 1.0) || $value === 0.0)
            return true;
        return false;
    }

    public static function filter($value)
    {
        if(is_string($value))
            return in_array($value, self::$true_variants, true) ? true : false;
        return (bool) $value;
    }

    public static function toDb($value)
    {
        return (int) self::filter($value);
    }

    public static function fromDb($value)
    {
        return (bool) $value;
    }

    public static function defaultValue()
    {
        return false;
    }

    public static function sqlType()
    {
        return Schema::TYPE_BOOL;
    }

}
