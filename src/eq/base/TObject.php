<?php

namespace eq\base;

use eq\helpers\Str;

trait TObject
{

    private static $_getters = [];
    private static $_setters = [];

    public static function isA($obj)
    {
        if(!is_object($obj))
            return false;
        if($obj instanceof Object)
            return true;
        $classes = class_parents($obj);
        array_unshift($classes, $obj);
        foreach($classes as $cls) {
            $traits = class_uses($cls);
            if(isset($traits['eq\base\TObject']))
                return true;
        }
        return false;
    }

    public static function cls()
    {
        return get_called_class();
    }

    public function __get($name)
    {
        $getter = $this->getterName($name);
        if(method_exists($this, $getter))
            return $this->{$getter}();
        elseif($this->setterExists($name))
            throw new InvalidCallException(
                "Getting write-only property: ".get_class($this)."::".$name);
        else
            throw new UnknownPropertyException(
                "Getting unknown property: ".get_class($this)."::".$name);
    }

    public function __set($name, $value)
    {
        $setter = $this->setterName($name);
        if(method_exists($this, $setter))
            $this->{$setter}($value);
        elseif($this->setterExists($name))
            throw new InvalidCallException(
                "Setting read-only property: ".get_class($this)."::".$name);
        else
            throw new UnknownPropertyException(
                "Setting unkown property: ".get_class($this)."::".$name);
    }

    public function __isset($name)
    {
        $getter = $this->getterName($name);
        return method_exists($this, $getter) ? $this->{$getter}() !== null : false;
    }

    public function __unset($name)
    {
        $setter = $this->setterName($name);
        if(method_exists($this, $setter))
            $this->{$setter}(null);
        elseif($this->getterExists($name))
            throw new InvalidCallException(
                "Unsetting read-only property: ".get_class($this)."::".$name);
    }

    public function getterName($name)
    {
        if(!isset(self::$_getters[$name]))
            self::$_getters[$name] = "get".Str::var2method($name);
        return self::$_getters[$name];
    }

    public function setterName($name)
    {
        if(!isset(self::$_setters[$name]))
            self::$_setters[$name] = "set".Str::var2method($name);
        return self::$_setters[$name];
    }

    public function getterExists($name)
    {
        return method_exists($this, $this->getterName($name));
    }

    public function setterExists($name)
    {
        return method_exists($this, $this->setterName($name));
    }

}
