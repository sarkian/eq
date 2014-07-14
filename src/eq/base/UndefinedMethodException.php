<?php

namespace eq\base;

class UndefinedMethodException extends ExceptionBase
{

    /**
     * @param object|string $cls
     * @param string $name
     */
    public function __construct($cls, $name)
    {
        $cname = is_object($cls) ? get_class($cls) : $cls;
        $msg = "Call to undefined method: $cname::$name()";
        parent::__construct($msg);
    }

} 