<?php

namespace eq\base;

class NotImplementedException extends ExceptionBase
{

    public function __construct($message = "Not implemented, yet")
    {
        parent::__construct($message);
    }

} 