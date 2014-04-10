<?php
/**
 * Last Change: 2014 Mar 15, 21:58
 */

namespace eq\datatypes;

class Email extends DataTypeBase
{

    public static function validate($value)
    {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

}
