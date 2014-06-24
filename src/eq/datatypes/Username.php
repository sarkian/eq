<?php

namespace eq\datatypes;

use eq\db\Schema;

class Username extends Str
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[a-zA-Z][a-zA-Z0-9]{4,32}$/", $value);
    }

    public static function isA($value)
    {
        return self::validate($value);
    }

    public static function sqlType()
    {
        return Schema::TYPE_TINYSTRING;
    }

}
