<?php
/**
 * Last Change: 2014 Apr 23, 17:27
 */

namespace eq\cgen\php;

class PhpObject
{

    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function render($indent = 0)
    {
        return str_repeat(" ", $indent * 4)."(object) "
            .ltrim((new PhpArray((array) $this->value))->render($indent));
    }

}
