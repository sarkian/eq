<?php

namespace eq\base;

class LoaderException extends ExceptionBase
{
    
    protected $type = "LoaderException";
    protected $trace;

    public function __construct($message, $trace = null)
    {
        parent::__construct($message);
        $this->trace = $trace;
        if(!$this->trace) return;
        array_shift($this->trace);
        foreach($this->trace as $call) {
            if(isset($call['file'], $call['line'])) {
                $this->file = $call['file'];
                $this->line = $call['line'];
                break;
            }
        }
    }

    public function _getTrace()
    {
        return $this->trace ? $this->trace : $this->getTrace();
    }
    
}
