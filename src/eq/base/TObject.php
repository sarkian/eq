<?php

namespace eq\base;

trait TObject
{

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
        return method_exists($this, $getter)
            ? $this->{$getter}() !== null : false;
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
        return 'get_'.$name;
        //        return "get".Str::var2method($name);
    }

    public function setterName($name)
    {
        return 'set_'.$name;
        //        return "set".Str::var2method($name);
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
