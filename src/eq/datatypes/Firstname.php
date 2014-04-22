<?php
/**
 * Last Change: 2014 Apr 19, 16:57
 */

namespace eq\datatypes;

class Firstname extends Str
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[\p{Cyrillic}]{3,255}$/u", $value);
    }

}
