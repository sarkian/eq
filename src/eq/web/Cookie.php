<?php

namespace eq\web;

use EQ;
use eq\helpers\Arr;

class Cookie implements \ArrayAccess
{

    protected $cookies = [];

    public function __construct()
    {
        EQ::app()->bind("beforeEcho", [$this, "__beforeEcho"]);
    }

    public function __beforeEcho()
    {
        foreach($this->cookies as $name => $cookie)
            setcookie(
                $name,
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
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
        $this->delete($name);
    }

    public function offsetExists($name)
    {
        return isset($_COOKIE[$name]);
    }

    public function offsetGet($name)
    {
        return $this->call($name);
    }

    public function offsetSet($name, $value)
    {
        if(is_null($value))
            $this->delete($name);
        else
            $this->call($name, $value);
    }

    public function offsetUnset($name)
    {
        $this->delete($name);
    }

    public function get($name, $default = null)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
    }

    public function call($name, $value = null, $options = [])
    {
        if($value === null)
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
        $options = Arr::extend($options, [
            'expire' => EQ::app()->time() + 91454400,
            'domain' => EQ::app()->request->host,
            'path' => "/",
            'secure' => EQ::app()->request->scheme === "https" ? true : false,
            'httponly' => true,
        ]);
        $options['value'] = $value;
        $this->cookies[$name] = $options;
        return null;
    }

    public function delete($name = null, $options = [])
    {
        if(!$name) {
            foreach($_COOKIE as $name => $value)
                $this->delete($name);
        }
        else {
            $options = Arr::extend($options, [
                'domain' => EQ::app()->request->host,
                'path' => "/",
                'secure' => EQ::app()->request->scheme === "https" ? true : false,
                'httponly' => true,
            ]);
            $options['value'] = false;
            $options['expire'] = null;
            $this->cookies[$name] = $options;
        }
    }

}
