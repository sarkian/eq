<?php

namespace eq\console;

class Argument extends ArgumentAbstract
{

    protected $index;
    protected $args;
    protected $name = null;
    protected $value = null;

    public function __construct($index, $name, Args $args)
    {
        $this->index = $index;
        $this->args = $args;
        if(preg_match('/^\-{1,2}([a-zA-Z0-9\-_]+)=(.+)$/', $name, $matches)) {
            $this->name = $matches[1];          // --name=value
            $this->value = $matches[2];
        }
        elseif(!strncmp("-", $name, 1))
            $this->name = ltrim($name, "--");   // --name
        else
            $this->value = $name;               // value
    }

    public function __toString()
    {
        return is_null($this->name) ? "" : "-".$this->name;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function hasPrev()
    {
        return $this->args->iExists($this->index - 1);
    }

    public function hasNext()
    {
        return $this->args->iExists($this->index + 1);
    }

    public function prev()
    {
        return $this->args->iGet($this->index - 1);
    }

    public function next()
    {
        return $this->args->iGet($this->index + 1);
    }

    public function hasName()
    {
        return !is_null($this->name);
    }

    public function hasValue()
    {
        return !is_null($this->value);
    }

}
