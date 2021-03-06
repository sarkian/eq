<?php

namespace eq\datatypes;

use eq\db\Schema;

class Email extends Str
{

    public static function validate($value)
    {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function isA($value)
    {
        return self::validate($value);
    }

    public static function sqlType()
    {
        return Schema::TYPE_SMALLSTRING;
    }

}
