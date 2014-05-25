<?php

namespace eq\base;

class ShellExecException extends ExceptionBase
{

    protected $type = "ShellExecException";

    public function __construct($message, $code = 0)
    {
        $this->message = $message;
        $this->code = $code;
    }

}
