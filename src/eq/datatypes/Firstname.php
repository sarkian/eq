<?php

namespace eq\datatypes;

use eq\db\Schema;

class Firstname extends Str
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[[:alpha:]]{3,255}$/u", $value);
    }

    public static function sqlType()
    {
        return Schema::TYPE_TINYSTRING;
    }

}
