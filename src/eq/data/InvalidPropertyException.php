<?php

namespace eq\data;

use eq\base\ExceptionBase;
use eq\base\TObject;
use eq\helpers\Debug;

/**
 * @property string class
 * @property string name
 * @property mixed  value
 */
class InvalidPropertyException extends ExceptionBase
{

    use TObject;

    protected $class;
    protected $name;
    protected $value;

    public function __construct($cls, $name, $value = null)
    {
        $cname = is_object($cls) ? get_class($cls) : $cls;
        if(func_num_args() > 2)
            $msg = "Invalid property value: $cname::\$$name: ".Debug::shortDump($value);
        else
            $msg = "Invalid property value: $cname::\$$name";
        $this->class = $cname;
        $this->name = $name;
        $this->value = $value;
        parent::__construct($msg);
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

} 