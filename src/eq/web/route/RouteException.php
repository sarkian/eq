<?php

namespace eq\web\route;

use eq\base\ExceptionBase;

class RouteException extends ExceptionBase
{

    protected $type = "RouteException";

    public function __construct($message, $file = null, $line = null)
    {
        parent::__construct($message);
        if($file) {
            $this->file = $file;
            $this->line = is_int($line) ? $line : 0;
            $this->message = $message." on line ".$this->line." in ".$file;
        }
    }

}
