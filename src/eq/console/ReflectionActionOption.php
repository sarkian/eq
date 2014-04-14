<?php
/**
 * Last Change: 2014 Apr 14, 16:36
 */

namespace eq\console;

class ReflectionActionOption
{

    use \eq\base\TObject;

    protected $action;
    protected $doctag;
    protected $name;
    protected $default_value = null;

    public function __construct($action, $name)
    {
        $this->action = $action;
        $this->name = $name;
        $this->doctag = $this->action->docblock->tag("option", null, "$".$name);
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
