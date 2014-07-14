<?php

namespace eq\datatypes;

use eq\db\Schema;

class Float extends DataTypeBase
{

    public static function validate($value)
    {
        if(is_float($value))
            return true;
        if(preg_match('/^(\-|)[0-9]*(\.|)[0-9]*$/', $value))
            return true;
        return false;
    }

    public static function filter($value)
    {
        return (float) $value;
    }

    public static function toDb($value)
    {
        return (float) $value;
    }

    public static function fromDb($value)
    {
        return (float) $value;
    }

    public static function defaultValue()
    {
        return 0.0;
    }

    public static function sqlType()
    {
        return Schema::TYPE_FLOAT;
    }

}
