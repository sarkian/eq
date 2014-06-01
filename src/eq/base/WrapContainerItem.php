<?php

namespace eq\base;

use EQ;

class WrapContainerItem extends Object
{

    /**
     * @var Object|mixed
     */
    protected $obj = null;

    protected $_get_method;
    protected $_set_method;
    protected $_isset_method;
    protected $_unset_method;
    protected $_call_method;

    public function __construct($obj)
    {
        $this->obj = $obj;
        if(is_object($obj)) {
            $this->_get_method = function($name) {
                return isset($this->obj->{$name}) ? $this->obj->{$name} : null;
            };
            $this->_set_method = function($name, $value) {
                $this->obj->{$name} = $value;
            };
            $this->_isset_method = function ($name) {
                return isset($this->obj->{$name});
            };
            $this->_unset_method = function ($name) {
                unset($this->obj->{$name});
            };
            $this->_call_method = function($name, $args) {
                return call_user_func_array([$this->obj, $name], $args);
            };
        }
        elseif(is_array($obj)) {
            $this->_get_method = function($name) {
                return isset($this->obj[$name]) ? $this->obj[$name] : null;
            };
            $this->_set_method = function($name, $value) {
                $this->obj[$name] = $value;
            };
            $this->_isset_method = function ($name) {
                return isset($this->obj[$name]);
            };
            $this->_unset_method = function ($name) {
                unset($this->obj[$name]);
            };
            $this->_call_method = function($name, $args) {};
        }
        else {
            $this->_get_method = function($name) { return null; };
            $this->_set_method = function($name, $value) {};
            $this->_isset_method = function($name) { return false; };
            $this->_unset_method = function($name) {};
            $this->_call_method = function($args) {};
        }
    }

    public function __get($name)
    {
        $getter = $this->getterName($name);
        if(method_exists($this, $getter))
            return $this->{$getter}();
        else
            return call_user_func($this->_get_method, $name);
    }

    public function __set($name, $value)
    {
        $setter = $this->setterName($name);
        if(method_exists($this, $setter))
            $this->{$setter}($value);
        else
            call_user_func($this->_set_method, $name, $value);
    }

    public function __isset($name)
    {
        $getter = $this->getterName($name);
        if(method_exists($this, $getter))
            return $this->{$getter}() !== null;
        else
            return call_user_func($this->_isset_method, $name);
    }

    public function __unset($name)
    {
        $setter = $this->setterName($name);
        if(method_exists($this, $setter))
            $this->{$setter}(null);
        else
            call_user_func($this->_unset_method, $name);
    }

    public function __call($name, $args)
    {
        return call_user_func($this->_call_method, $name, $args);
    }

}