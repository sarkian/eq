<?php

namespace eq\console;

use eq\base\TObject;

/**
 * @property string name
 * @property string type
 * @property string description
 */
class ReflectionActionOption
{

    use TObject;

    protected $action;
    protected $doctag;
    protected $name;
    protected $default_value = null;

    public function __construct(ReflectionAction $action, $name)
    {
        $this->action = $action;
        $this->name = $name;
        $this->doctag = $this->action->docblock->tag("option", null, '/\$?'.preg_quote($name).'/');
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->doctag->wfirst();
    }

    public function getDescription()
    {
        return $this->doctag->fromsecond();
    }

    public function __toString()
    {
        $name = (strlen($this->name) > 1 ? "--" : "-").$this->name;
        return $this->type == "bool" ? "[$name]" : "[$name <value>]";
    }

}
