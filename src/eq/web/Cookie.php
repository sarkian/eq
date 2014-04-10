<?php
/**
 * Last Change: 2014 Apr 09, 02:43
 */

namespace eq\web;

use EQ;

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
                $this->host,
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

    public function call($name, $value = null, $expire = null,
                        $path = "/", $secure = null, $httponly = false)
    {
        if(is_null($value))
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
        if(is_null($expire))
            $expire = EQ::app()->time() + 91454400;
        if(is_null($secure))
            $secure = $this->scheme === "https" ? true : false;
        $this->cookies[$name] = [
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'secure' => $secure,
            'httponly' => $httponly,
        ];
    }

    public function delete($name = null)
    {
        if(!$name) {
            foreach($_COOKIE as $name => $value)
                $this->deleteCookie($name);
        }
        else
            $this->cookies[$name] = [
                'value' => null,
                'expire' => EQ::app()->time() - 91454400,
                'path' => "/",
            ];
    }

}
