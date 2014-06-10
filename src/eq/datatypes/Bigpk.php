<?php

namespace eq\datatypes;

class Bigpk extends Uintp
{

    public static function sqlType($engine = null)
    {
        return "bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    }

}