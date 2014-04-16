<?php
/**
 * Last Change: 2014 Mar 16, 17:26
 */

namespace favto\datatypes;

class Invite extends \eq\datatypes\DataTypeBase
{

    public static function validate($value)
    {
        return preg_match("/^[a-z]+\s[a-z]+$/", $value);
    }

}
