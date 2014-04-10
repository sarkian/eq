<?php

namespace eq\console;

use EQ;

class ReflectionAction extends \ReflectionMethod
{

    private $command;
    private $method;

    public function __construct($command, $method)
    {
        $this->command = $command;
        $this->method = $method;
        parent::__construct($this->command, $method);
    }

    public function run()
    {
        $required = [];
        $optional = [];
        foreach($this->getParameters() as $param) {
            if($param->isDefaultValueAvailable())
                $optional[$param->name] = $param->getDefaultValue();
            else
                $required[] = $param->name;
        }
        list($args, $opts) = EQ::app()->parseCmd($required, $optional);
        return \call_user_func_array([$this->command, $this->method], $args);
    }

    public function getDescription()
    {
        $comment = $this->getDocComment();
        if(!$comment) return '';
        $lines = \preg_split("/[\r\n]+/", $comment);
        $descr = isset($lines[1]) ? preg_replace("/^[\s\t]*\*[\s\t]+/", '', $lines[1]) : '';
        if(substr($descr, 0, 1) === '@') $descr = '';
        return $descr;
    }

    public function getParamsDoc()
    {
        $doc = '';
        $required = [];
        $optional = [];
        foreach($this->getParameters() as $param) {
            if($param->isDefaultValueAvailable())
                $optional[] = "[, <{$param->name}>";
            else
                $required[] = "<{$param->name}>";
        }
        $doc = \implode(' ', $required);
        if($optional)
            $doc .= ' '.\implode(' ', $optional).\str_pad(']', count($optional), ']');
        return $doc;
    }

}
