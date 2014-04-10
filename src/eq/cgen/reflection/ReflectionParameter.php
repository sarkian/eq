<?php
/**
 * Last Change: 2013 Oct 16, 20:10
 */

namespace eq\cgen\reflection;

class ReflectionParameter extends \ReflectionParameter implements IDefinitionString
{

    protected $method_instance;

    public function __construct($function, $parameter)
    {
        if($function instanceof \ReflectionMethod) {
            parent::__construct([$function->class, $function->name], $parameter);
            $this->method_instance = $function;
        }
        else
            parent::__construct($function, $parameter);
    }

    public function getDefinitionCodeString()
    {

    }

    public function getDefinitionProtoString()
    {
        $str = $this->getDeclaringFunction()->getDocParamType($this->name).' '.
            '$'.$this->name;
        if($this->isDefaultValueAvailable())
            $str .= ' = '.$this->normalizeDefaultValue();
        return $str;
    }

    public function getModifiersString()
    {
        return '';
    }

    public function getDocDescription()
    {
        $method = $this->getDeclaringFunction();
        if(\method_exists($method, 'getDocParamDescr'))
            return $this->getDeclaringFunction()->getDocParamDescr($this->name);
        else return '';
    }

    public function getDeclaringFunction()
    {
        if($this->method_instance)
            return $this->method_instance;
        else return parent::getDeclaringFunction();
    }

    protected function normalizeDefaultValue()
    {
        $value = $this->getDefaultValue();
        if(\method_exists($this, 'isDefaultValueConstant') && $this->isDefaultValueConstant())
            return $this->getDefaultValueConstantName();
        elseif(\is_null($value))
            return 'null';
        elseif(\is_string($value))
            return '"'.\str_replace('"', '\"', $value).'"';
        elseif(\is_bool($value))
            return $value ? 'true' : 'false';
        else
            return (string) $value;
    }

}
