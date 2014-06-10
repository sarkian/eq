<?php

namespace eq\modules\dbconfig\datatypes;

use eq\datatypes\DataTypeBase;

class Name extends DataTypeBase
{

    public static function sqlType($engine = null)
    {
        return "varchar(255) NOT NULL PRIMARY KEY";
    }

} 