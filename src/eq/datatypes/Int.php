<?php

namespace eq\datatypes;

class Int extends DataTypeBase
{

    public static function validate($value)
    {
        if(\is_int($value)) return true;
        if(\preg_match('/^(\-|)[0-9]+$/', $value))
            return true;
        return false;
    }

    public static function pattern()
    {
        return "\-{0,1}[0-9]+";
    }

    public static function filter($value)
    {
        return (int) $value;
    }

    public static function toDb($value)
    {
        return (int) $value;
    }

    public static function fromDb($value)
    {
        return (int) $value;
    }

    public static function defaultValue()
    {
        return 0;
    }

}
