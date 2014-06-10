<?php

namespace eq\datatypes;

class Password extends Str
{

    public static function validate($value)
    {
        return (bool) preg_match("/^.{4,32}$/", $value);
    }

    public static function formControl()
    {
        return "passwordField";
    }

    public static function sqlType($engine = null)
    {
        return "VARCHAR(32)";
    }
}
