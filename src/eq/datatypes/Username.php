<?php
/**
 * Last Change: 2014 Apr 19, 16:58
 */

namespace eq\datatypes;

class Username extends Str
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[a-zA-Z][a-zA-Z0-9]{4,32}$/", $value);
    }

}
