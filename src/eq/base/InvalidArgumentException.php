<?php

namespace eq\base;

use eq\helpers\Debug;

class InvalidArgumentException extends ExceptionBase
{

    protected $type = "InvalidArgumentException";

    /**
     * @param string|object $cls
     * @param string $method
     * @param string $arg
     * @param mixed $value
     */
    public function __construct($cls, $method, $arg, $value = null)
    {
        $cname = is_object($cls) ? get_class($cls) : $cls;
        if(func_num_args() > 3)
            $msg = "Invalid argument value: $cname::$method($arg): ".Debug::shortDump($value);
        else
            $msg = "Invalid argument: $cname::$method($arg)";
        parent::__construct($msg);
    }

}
