<?php

namespace eq\console;

use eq\base\TObject;
use eq\datatypes\DataTypeBase;

/**
 * @property DataTypeBase type
 * @property string description
 * @property bool required
 * @property bool multi
 */
class ReflectionActionParameter extends \ReflectionParameter
{

    use TObject;

    protected $action;
    protected $doctag;

    private $_multi = null;

    public function __construct(ReflectionAction $action, $name)
    {
        parent::__construct([$action->class, $action->name], $name);
        $this->action = $action;
        $this->doctag = $this->action->docblock->tag(
            "param", null, '/^\$'.preg_quote($name, "/").'\,?$/');
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
        $descr = $this->doctag->fromsecond();
        if($this->multi)
            $descr = preg_replace('/^\.\.\. /', '', $descr);
        return $descr;
    }

    public function getRequired()
    {
        return !$this->isDefaultValueAvailable();
    }

    public function getMulti()
    {
        if($this->_multi === null)
            $this->_multi = preg_match('/\,$/', $this->doctag->wsecond()) ? true : false;
        return $this->_multi;
    }

    public function __toString()
    {
        $name = $this->name;
        if($this->multi)
            $name .= ", ...";
        return $this->required ? "<{$name}>" : "[{$name}]";
    }

}
