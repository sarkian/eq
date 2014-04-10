<?php

namespace eq\db;

use eq\base\UnknownPropertyException;

class Pool extends \eq\base\Object
{

    protected $connections = [];

    public function __construct($config)
    {
        foreach($config as $name => $dbconf)
            $this->addDb($name, $dbconf);
    }

    public function addDb($name, $config)
    {
        if(isset($this->connections[$name]))
            throw new DbException("Database already exists in pool: $name");
        $this->connections[$name] = ConnectionBase::create($config);
    }

    public function call($name = null)
    {
        if(!$this->connections)
            throw new DbException("Pool is empty");
        if($name)
            return $this->__get($name);
        $keys = array_keys($this->connections);
        $key = array_shift($keys);
        return $this->connections[$key];
    }

    public function __get($name)
    {
        if(!$this->connections)
            throw new DbException("Pool is empty");
        if(isset($this->connections[$name]))
            return $this->connections[$name];
        else
            throw new UnknownPropertyException("Unknown database: $name");
    }

}
