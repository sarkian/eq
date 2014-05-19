<?php
/**
 * Last Change: 2014 Apr 19, 16:58
 */

namespace eq\datatypes;

class Phone extends Str
{

    public static function validate($value)
    {
        return (bool) preg_match('/^\({0,1}[0-9]{3}\){0,1}[\-\s]{0,1}[0-9]{3}[\-\s]{0,1}[0-9]{2}[\-\s]{0,1}[0-9]{2}$/', $value);
    }

    public static function isA($value)
    {
        return self::validate($value);
    }

}
