<?php

namespace eq\datatypes;

class Ufloatx extends Ufloat
{

    public static function isEmpty($value)
    {
        return (float) $value === 0.0 || parent::isEmpty($value);
    }

}