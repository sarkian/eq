<?php

namespace eq\datatypes;

class Uintx extends Uint
{

    public static function isEmpty($value)
    {
        return (int) $value === 0 || parent::isEmpty($value);
    }

}