<?php


namespace eq\base;


use eq\helpers\Dbg;
use eq\helpers\Debug;

class AssertionFailedException extends ExceptionBase
{

    protected $type = "AssertionFailedException";

    public function __construct($assertion, $description = null)
    {
        list($this->file, $this->line) = Debug::callLocation(2);
        if(is_string($description) && $description)
            $this->message = $description;
        else
            $this->message = "Assertion failed";
    }

} 