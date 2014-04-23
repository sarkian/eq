<?php
/**
 * Last Change: 2014 Apr 23, 17:23
 */

namespace eq\cgen\php;

class PhpArray
{

    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function render($indent = 0)
    {
        $indent_str = str_repeat(" ", $indent * 4);
        $code = [$indent_str."["];
        foreach($this->value as $name => $value) {
            $line = $indent_str."    ".(new PhpScalar($name))->render()." => ";
            $line .= ltrim(PhpValue::create($value)->render($indent + 1)).",";
            $code[] = $line;
        }
        $code[] = $indent_str."]";
        return implode("\n", $code);
    }

}
