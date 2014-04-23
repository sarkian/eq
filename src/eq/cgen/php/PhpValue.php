<?php
/**
 * Last Change: 2014 Apr 23, 21:43
 */

namespace eq\cgen\php;

class PhpValue
{

    public static function create($value, $allow_objects = false)
    {
        if(is_scalar($value) || is_null($value))
            return new PhpScalar($value);
        elseif(is_object($value))
            return $allow_objects ? new PhpObject($value) : new PhpArray($value);
        elseif(is_array($value))
            return new PhpArray($value);
        else
            return new PhpScalar("*UNKNOWN_TYPE*");
    }

}
