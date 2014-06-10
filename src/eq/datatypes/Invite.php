<?php

namespace eq\datatypes;

class Invite extends Str
{

    public static function validate($value)
    {
        return preg_match("/^[a-z]+\s[a-z]+$/", $value);
    }

    public static function sqlType($engine = null)
    {
        return "VARCHAR(255)";
    }
}
