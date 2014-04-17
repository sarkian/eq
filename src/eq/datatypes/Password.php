<?php
/**
 * Last Change: 2014 Apr 17, 14:15
 */

namespace eq\datatypes;

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
