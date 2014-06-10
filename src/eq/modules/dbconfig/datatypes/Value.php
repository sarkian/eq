<?php

namespace eq\modules\dbconfig\datatypes;

use eq\datatypes\DataTypeBase;

class Value extends DataTypeBase
{

    public static function sqlType($engine = null)
    {
        return "mediumtext";
    }

}