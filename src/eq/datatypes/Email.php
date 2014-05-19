<?php
/**
 * Last Change: 2014 Apr 19, 16:57
 */

namespace eq\datatypes;

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

}
