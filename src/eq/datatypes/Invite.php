<?php
/**
 * Last Change: 2014 Apr 17, 14:15
 */

namespace eq\datatypes;

class Invite extends \eq\datatypes\DataTypeBase
{

    public static function validate($value)
    {
        return preg_match("/^[a-z]+\s[a-z]+$/", $value);
    }

}
