<?php
/**
 * Last Change: 2013 Oct 16, 20:14
 */

namespace eq\cgen\reflection;

class ReflectionMethod extends \ReflectionMethod implements IDefinitionString
{

    use TDocBlock;

    public function __construct($class, $name)
    {
        parent::__construct($class, $name);
        $this->processDocBlock();
    }

    public function getDocReturnType()
    {
        $tags = $this->getDocblockTag('return');
        if(isset($tags[0][0])) {
            if(\strpos($tags[0][0], '|') === false)
                return $tags[0][0];
            else return 'mixed';
        }
        else return 'void';
    }

    public function getDocReturnDescr()
    {
        $tags = $this->getDocblockTag('return');
        if(isset($tags[0][1]))
            return \implode(' ', \array_slice($tags[0], 1));
        else return '';
    }

    public function getDefinitionCodeString()
    {

    }

    public function getDefinitionProtoString()
    {
        return $this->getModifiersString().' '.
            $this->getDocReturnType().' '.
            $this->name.' ( '.
            $this->getParamsProtoStr().' )';
    }

    public function getModifiersString()
    {
        $str = [];
        if($this->isPublic())
            $str[] = 'public';
        if($this->isProtected())
            $str[] = 'protected';
        if($this->isPrivate())
            $str[] = 'private';
        if($this->isStatic())
            $str[] = 'static';
        if($this->isAbstract())
            $str[] = 'abstract';
        if($this->isFinal())
            $str[] = 'final';
        return \implode(' ', $str);
    }

    public function getParamsProtoStr()
    {
        if(!$this->getNumberOfParameters())
            return 'void';
        $str = '';
        $required = [];
        $optional = [];
        foreach($this->getParameters() as $param) {
            $param_proto = $param->getDefinitionProtoString();
            if(!$param->isDefaultValueAvailable())
                $required[] = $param_proto;
            elseif($this->getNumberOfParameters() == 1)
                return "[ $param_proto ]";
            else
                $optional[] = "[, $param_proto";
        }
        $str = \implode(', ', $required);
        if($optional) {
            if($required) $str .= ' ';
            $str .= \implode(' ', $optional).' '.\str_pad(']', \count($optional), ']');
        }
        return $str;
    }

    public function getParameters()
    {
        $params = parent::getParameters();
        return \array_map(function(\ReflectionParameter $param) {
            return new ReflectionParameter($this, $param->name);
        }, $params);
    }

}
