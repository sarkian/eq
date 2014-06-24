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
        parent::__construct($message, $code, $exception);
    }

}
