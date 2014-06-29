<?php

namespace eq\console;

use eq\base\TObject;
use eq\cgen\base\docblock\Docblock;

/**
 * @property Command command
 * @property string command_class
 * @property Docblock docblock
 * @property string short_description
 * @property ReflectionActionParameter[] parameters
 * @property string parameters_str
 * @property ReflectionActionOption[] options
 * @property string options_str
 */
class ReflectionAction extends \ReflectionMethod
{

    protected $command;
    protected $method;
    protected $docblock;

    protected $_params = [];
    protected $_opts = [];

    use TObject;

    public function __construct($command, $method)
    {
        $this->command = $command instanceof Command ? $command : $command::inst();
        $this->method = $method;
        parent::__construct($this->command, $method);
        $this->docblock = new Docblock($this->getDocComment());
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getCommandClass()
    {
        return get_class($this->command);
    }

    public function getDocblock()
    {
        return $this->docblock;
    }

    public function getShortDescription()
    {
        return $this->docblock->shortDescription();
    }

    public function getParameters()
    {
        $params = [];
        foreach(parent::getParameters() as $param) {
            $params[] = $this->getParameter($param->name);
        }
        return $params;
    }

    public function getParameter($name)
    {
        if(!isset($this->_params[$name]))
            $this->_params[$name] = new ReflectionActionParameter($this, $name);
        return $this->_params[$name];
    }

    public function getParametersStr()
    {
        return implode(" ", $this->parameters);
    }

    public function getOptions()
    {
        $opts = [];
        foreach($this->docblock->tag("option")->wsecondAll() as $name) {
            if(strlen($name))
                $opts[] = $this->getOption($name);
        }
        return $opts;
    }

    public function getOption($name)
    {
        $name = preg_replace("/^\\$/", "", $name);
        if(!isset($this->_opts[$name]))
            $this->_opts[$name] = new ReflectionActionOption($this, $name);
        return $this->_opts[$name];
    }

    public function getOptionsStr()
    {
        return implode(" ", $this->options);
    }

}
