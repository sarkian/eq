<?php

namespace eq\web;

use EQ;
use eq\base\ExceptionBase;

class HttpException extends ExceptionBase
{

    protected $type = "HttpException";
    protected $status;

    public function __construct($status, $message = null, $code = 0)
    {
        $this->status = $status;
        parent::__construct(EQ::t($message), $code);
        if(EQ::app())
            EQ::app()->http_exception = $this;
    }

    public function getStatus()
    {
        return $this->status;
    }
    
}
