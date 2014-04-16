<?php
/**
 * Last Change: 2014 Apr 09, 13:35
 */

namespace favto\datatypes;

class Password extends \eq\datatypes\DataTypeBase
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
