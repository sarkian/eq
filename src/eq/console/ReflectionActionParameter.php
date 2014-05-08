<?php

namespace eq\console;

use eq\base\TObject;
use eq\datatypes\DataTypeBase;

/**
 * @property DataTypeBase type
 * @property string description
 * @property bool required
 */
class ReflectionActionParameter extends \ReflectionParameter
{

    use TObject;

    protected $action;
    protected $doctag;

    public function __construct(ReflectionAction $action, $name)
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
