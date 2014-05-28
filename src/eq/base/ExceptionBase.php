<?php

namespace eq\base;

abstract class ExceptionBase extends \Exception
{

    protected $code = "";
    protected $type = "ExceptionBase";

    public function __construct($message = "", $code = 0, $previous = null) {
        $this->code = $code;
        is_int($code) or $code = 0;
        parent::__construct($message, $code, $previous);
        if(class_exists("EQ", false) && \EQ::app()) {
            \EQ::app()->trigger("exceptionConstruct", $this);
        }
    }
    
    public function getType()
    {
        return $this->type;
    }

    public function _getTrace()
    {
        return $this->getTrace();
    }

}
