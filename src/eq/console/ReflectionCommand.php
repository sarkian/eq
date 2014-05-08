<?php

namespace eq\console;

use eq\helpers\Str;
use eq\cgen\base\docblock\Docblock;

class ReflectionCommand extends \ReflectionClass
{

    protected $docblock;

    protected $_actions = [];

    public function __construct($argument)
    {
        parent::__construct($argument);
        $this->docblock = new Docblock($this->getDocComment());
    }

    public function getActions()
    {
        $actions = [];
        foreach($this->getMethods() as $method) {
            if(preg_match("/^action([A-Z][a-zA-Z]+)/", $method->name, $matches))
                $actions[Str::method2cmd($matches[1])]
                    = $this->getAction($method->name);
        }
        return $actions;
    }

    /**
     * @param string $name
     * @return ReflectionAction
     */
    public function getAction($name)
    {
        if(!preg_match("/^action/", $name))
            $name = "action".Str::cmd2method($name);
        if(!isset($this->_actions[$name]))
            $this->_actions[$name] = new ReflectionAction($this->name, $name);
        return $this->_actions[$name];
    }

    public function getShortDescription()
    {
        return $this->docblock->shortDescription();
    }

    public function actionExists($name)
    {
        $method = "action".Str::cmd2method($name);
        return $this->hasMethod($method);
    }

}
