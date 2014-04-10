<?php
/**
 * Last Change: 2014 Feb 08, 15:09
 */

namespace eq\base;

class TestException extends ExceptionBase
{

    protected $type = "TestException";

    public function test()
    {
        $this->file = __FILE__;
    }

}
