<?php
/**
 * Last Change: 2014 Apr 19, 16:58
 */

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

}
