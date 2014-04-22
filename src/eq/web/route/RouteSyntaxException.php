<?php
/**
 * Last Change: 2014 Apr 20, 01:15
 */

namespace eq\web\route;

class RouteSyntaxException extends RouteException
{

    protected $type = "RouteSyntaxException";

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
