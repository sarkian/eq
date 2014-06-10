<?php

namespace eq\datatypes;

class Pk extends Uintp
{

    public static function sqlType($engine = null)
    {
        return "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    }

} 