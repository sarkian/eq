<?php
/**
 * Last Change: 2014 Apr 14, 10:47
 */

namespace eq\console;

use EQ;

class Args extends ArgumentAbstract
{

    protected $args = [];
    protected $arg_indexes = [];
    protected $opt_indexes = [];

    public function __construct()
    {
        $args = EQ::app()->argv;
        array_shift($args);
        foreach($args as $i => $argstr) {
            $arg = new Argument($i, $argstr, $this);
            if(!$arg->hasName() && $arg->hasValue())
                $this->arg_indexes[] = $i;
            $this->args[$i] = $arg;
        }
    }

    public function reset()
    {
        $this->opt_indexes = [];
    }

    public function option($names, $default = null)
    {
        if(!is_array($names))
            $names = [$names];
        foreach($names as $name) {
            $name = "-".ltrim($name, "-");
            $arg = $this->get($name);
            if(is_null($arg))
                continue;
            if(is_bool($default))
                return true;
            if($arg->hasValue())
                return $arg->getValue();
            $narg = $arg->next();
            if(!$narg)
                continue;
            if(!$narg->hasName() && $narg->hasValue()) {
                if(!in_array($narg->getIndex(), $this->opt_indexes))
                    $this->opt_indexes[] = $narg->getIndex();
                return $narg->getValue();
            }
        }
        return $default;
    }

    public function argument($index, $default = null)
    {
        $indexes = array_merge(
            array_diff($this->arg_indexes, $this->opt_indexes));
        return isset($indexes[$index]) 
            ? $this->iGet($indexes[$index])->getValue() : $default;
    }

    public function arguments($offset = 0)
    {
        
    }

    protected function exists($name)
    {
        return in_array($name, $this->args);
    }

    protected function get($name)
    {
        $key = array_search($name, $this->args);
        return $key === false ? null : $this->args[$key];
    }

    protected function iExists($index)
    {
        if($index < 0)
            return null;
        return isset($this->args[$index]);
    }

    protected function iGet($index)
    {
        if($index < 0)
            return null;
        return isset($this->args[$index]) ? $this->args[$index] : null;
    }

}
