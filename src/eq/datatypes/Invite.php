<?php

namespace eq\datatypes;

use eq\db\Schema;

class Invite extends Str
{

    public static function validate($value)
    {
        return preg_match('/^[a-z]+\s[a-z]+$/', $value);
    }

    public static function sqlType()
    {
        return Schema::TYPE_SMALLSTRING;
    }
}
