<?php
/**
 * Last Change: 2014 Apr 23, 17:19
 */

namespace eq\cgen\php;

class PhpScalar
{

    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function render($indent = 0)
    {
        $indent_str = str_repeat(" ", $indent * 4);
        if(is_string($this->value))
            return $indent_str."'".str_replace("'", "\\'", $this->value)."'";
        elseif(is_numeric($this->value))
            return $indent_str.((string) $this->value);
        elseif(is_bool($this->value))
            return $indent_str.($this->value ? "true" : "false");
        else
            return "null";
    }

}
