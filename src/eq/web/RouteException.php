<?php
/**
 * Last Change: 2014 Feb 22, 05:59
 */

namespace eq\web;

class RouteException extends \eq\base\ExceptionBase
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
