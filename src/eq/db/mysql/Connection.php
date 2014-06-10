<?php

namespace eq\db\mysql;

use eq\db\ConnectionBase;
use eq\helpers\Arr;
use eq\base\InvalidConfigException;

use EQ;
use PDO;

class Connection extends ConnectionBase
{

    protected $driver = "mysql";
    protected $host;
    protected $dbname;
    protected $user;
    protected $pass;

    public function __construct($name, $config)
    {
        parent::__construct($name, $config);
        $this->host = Arr::getItem($config, "host", "localhost");
        $this->dbname = Arr::getItem($config, "dbname", EQ::app()->app_namespace);
        $this->user = Arr::getItem($config, "user", EQ::app()->app_namespace);
        $this->pass = Arr::getItem($config, "pass", "");
        if(!$this->dbname)
            throw new InvalidConfigException("Missing parameter: dbname");
    }

    protected function createPDOInstance()
    {
        $this->pdo = new PDO($this->createDSN(), $this->user, $this->pass);
    }

    protected function createDSN()
    {
        return "mysql:dbname={$this->dbname};host={$this->host}";
    }

    protected function createSchema()
    {
        return new Schema($this);
    }

}
