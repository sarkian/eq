<?php

namespace eq\datatypes;

class Ufloatp extends Ufloat
{

    public static function validate($value)
    {
        if((float) $value <= 0)
            return false;
        return parent::validate($value);
    }

}