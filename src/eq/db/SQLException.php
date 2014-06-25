<?php

namespace eq\db;

use eq\base\ExceptionBase;

class SQLException extends ExceptionBase
{

    protected $type = "SQLException";

    public function __construct($message, $code, $query = null, $exception = null)
    {
        if($query)
            $message .= "; Query: $query";
        if(is_string($code) && preg_match("/^[0-9]+$/", $code))
            $code = (int) $code;
        parent::__construct($message, $code, $exception);
    }

}
