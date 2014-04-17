<?php
/**
 * Last Change: 2014 Apr 17, 14:14
 */

namespace eq\datatypes;

class Firstname extends \eq\datatypes\DataTypeBase
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[\p{Cyrillic}]{3,255}$/u", $value);
    }

}
