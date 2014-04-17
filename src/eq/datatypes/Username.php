<?php
/**
 * Last Change: 2014 Apr 17, 14:15
 */

namespace eq\datatypes;

class Username extends \eq\datatypes\DataTypeBase
{

    public static function validate($value)
    {
        return (bool) preg_match("/^[a-zA-Z][a-zA-Z0-9]{4,32}$/", $value);
    }

}
