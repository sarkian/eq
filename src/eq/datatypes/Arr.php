<?php

namespace eq\datatypes;

use eq\db\Schema;

class Arr extends DataTypeBase
{

    public static function validate($value)
    {
        return is_array($value);
    }

    public static function filter($value)
    {
        return (array) $value;
    }

    public static function toDb($value)
    {
        return json_encode((array) $value);
    }

    public static function fromDb($value)
    {
        return json_decode($value, true);
    }

    public static function cast($value)
    {
        return (array) $value;
    }

    public static function isA($value)
    {
        return is_array($value);
    }

    public static function defaultValue()
    {
        return [];
    }

    public static function sqlType()
    {
        return Schema::TYPE_TEXT;
    }

} 