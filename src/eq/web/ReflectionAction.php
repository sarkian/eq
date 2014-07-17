<?php

namespace eq\web;

use EQ;
use eq\base\TObject;
use eq\cgen\base\docblock\Docblock;
use eq\datatypes\DataTypeBase;
use eq\web\route\RouteException;


/**
 * @property Docblock docblock
 */
class ReflectionAction extends \ReflectionMethod
{

    use TObject;

    protected $instance;
    protected $_docblock = null;

    public function __construct(Controller $instance, $name)
    {
        parent::__construct($instance, $name);
        $this->instance = $instance;
    }

    public function getArgsNames()
    {
        $args = [];
        foreach($this->getParameters() as $arg)
            $args[] = $arg->name;
        return $args;
    }

    public function getDocblock()
    {
        if(!$this->_docblock)
            $this->_docblock = new Docblock($this->getDocComment());
        return $this->_docblock;
    }

    public function call($args = [])
    {
        $params = [];
        $docblock = null;
        foreach($this->getParameters() as $i => $param) {
            $name = $param->name;
            if(isset($args[$name])) {
                $typename = $this->argDocType($name);
                if($typename) {
                    $type = DataTypeBase::getClass($typename);
                    $params[$i] = $type::filter($args[$name]);
                }
                else
                    $params[$i] = $args[$name];
            }
            elseif($param->isDefaultValueAvailable()) {
                $params[$i] = $param->getDefaultValue();
            }
            else {
                throw new RouteException(
                    "Missing argument for {$this->class}::{$this->name}(): $name");
            }
        }
        return $this->invokeArgs($this->instance, $params);
    }

    public function argDocType($name)
    {
        return $this->docblock->tag("param", null, "\$$name")->wfirst();
    }

}
