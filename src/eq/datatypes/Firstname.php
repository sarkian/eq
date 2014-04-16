<?php
/**
 * Last Change: 2014 Mar 15, 20:44
 */

namespace favto\datatypes;

class Firstname extends \eq\datatypes\DataTypeBase
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[\p{Cyrillic}]{3,255}$/u", $value);
    }

}
