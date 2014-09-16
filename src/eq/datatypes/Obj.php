<?php

namespace eq\datatypes;

use eq\db\mysql\Schema;

class Obj extends DataTypeBase
{

    public static function isEmpty($value)
    {
        return (bool) (array) $value;
    }

    public static function validate($value)
    {
        return is_object($value);
    }

    public static function filter($value)
    {
        return (object) $value;
    }

    public static function toDb($value)
    {
        return json_encode($value);
    }

    public static function fromDb($value)
    {
        return json_decode($value);
    }

    public static function cast($value)
    {
        return (object) $value;
    }

    public static function isA($value)
    {
        return is_object($value);
    }

    public static function defaultValue()
    {
        return (object) [];
    }

    public static function sqlType()
    {
        return Schema::TYPE_TEXT;
    }

} 