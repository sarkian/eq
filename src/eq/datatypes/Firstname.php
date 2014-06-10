<?php

namespace eq\datatypes;

class Firstname extends Str
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[[:alpha:]]{3,255}$/u", $value);
    }

    public static function sqlType($engine = null)
    {
        return "VARCHAR(255)";
    }

}
