<?php

namespace eq\base;

class UncaughtExceptionException extends ExceptionBase
{

    protected $type = "UncaughtExceptionException";
    protected $exception;

    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
        parent::__construct($exception->getMessage(), $exception->getCode());
        $this->file = $exception->getFile();
        $this->line = $exception->getLine();
    }

    public function getException()
    {
        return $this->exception;
    }

    public function _getTrace()
    {
        return $this->exception->getTrace();
    }

}
