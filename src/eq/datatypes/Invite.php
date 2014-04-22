<?php
/**
 * Last Change: 2014 Apr 19, 16:58
 */

namespace eq\datatypes;

class Invite extends Str
{

    public static function validate($value)
    {
        return preg_match("/^[a-z]+\s[a-z]+$/", $value);
    }

}
