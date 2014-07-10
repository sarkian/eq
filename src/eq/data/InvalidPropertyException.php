<?php

namespace eq\data;

use eq\base\ExceptionBase;
use eq\helpers\Debug;

class InvalidPropertyException extends ExceptionBase
{

    public function __construct($cls, $name, $value = null)
    {
        $cname = is_object($cls) ? get_class($cls) : $cls;
        if(func_num_args() > 1)
            $msg = "Invalid property value: $cname::$name: ".Debug::shortDump($value);
        else
            $msg = "Invalid property: $cname::$name";
        parent::__construct($msg);
    }

} 