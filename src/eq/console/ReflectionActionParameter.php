<?php
/**
 * Last Change: 2014 Apr 14, 17:11
 */

namespace eq\console;

use eq\datatypes\DataTypeBase;

class ReflectionActionParameter extends \ReflectionParameter
{

    use \eq\base\TObject;

    protected $action;
    protected $doctag;

    public function __construct($action, $name)
    {
        parent::__construct([$action->class, $action->name], $name);
        $this->action = $action;
        $this->doctag = $this->action->docblock->tag("param", null, '$'.$name);
    }

    public function getType()
    {
        if($this->isDefaultValueAvailable()) {
            $val = $this->getDefaultValue();
            return DataTypeBase::getTypeForValue($val);
        }
        $type = $this->doctag->wfirst();
        return $type ? $type : "str";
    }

    public function getDescription()
    {
        return $this->doctag->fromsecond();
    }

    public function getRequired()
    {
        return !$this->isDefaultValueAvailable();
    }

    public function __toString()
    {
        return $this->required ? "<{$this->name}>" : "[{$this->name}]";
    }

}
