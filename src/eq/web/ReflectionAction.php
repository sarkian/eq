<?php
/**
 * Last Change: 2014 Feb 22, 06:13
 */

namespace eq\web;

use EQ;


class ReflectionAction extends \ReflectionMethod
{

    protected $instance;

    public function __construct($instance, $name)
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

    public function call($args = [])
    {
        foreach($this->getParameters() as $param) {
            if(!isset($args[$param->name]) 
                    && !$param->isDefaultValueAvailable())
                throw new RouteException(
                    "Missed action argument: {$param->name}",
                    EQ::app()->route->found_rule->file,
                    EQ::app()->route->found_rule->line
                );
        }
        return $this->invokeArgs($this->instance, $args);
    }

}
