<?php
/**
 * Last Change: 2014 Apr 10, 13:26
 */

namespace eq\web;

use EQ;

class Header implements \ArrayAccess
{

    protected $headers = [];

    public function __construct()
    {
        EQ::app()->bind("beforeEcho", [$this, "__beforeEcho"]);
    }

    public function __beforeEcho()
    {
        foreach($this->headers as $name => $value)
            header("$name: $value");
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __get($name)
    {
        return $this->call($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function offsetExists($name)
    {
        return isset($this->headers[$name]);
    }

    public function offsetGet($name)
    {
        return $this->call($name);
    }

    public function offsetSet($name, $value)
    {
        if(is_null($value))
            unset($this->headers);
        else
            $this->headers[$name] = $value;
    }

    public function offsetUnset($name)
    {
        unset($this->headers);
    }

    public function call($name, $value = null)
    {
        if(is_null($value)) {
            $hname = "HTTP_".strtoupper($name);
            return isset($_SERVER[$hname]) ? $_SERVER[$hname] : null;
        }
        $this->headers[$name] = $value;
    }

}
