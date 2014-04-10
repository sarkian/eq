<?php
/**
 * Last Change: 2014 Mar 15, 16:36
 */

namespace eq\db\mysql;

use eq\helpers\Arr;
use eq\base\InvalidConfigException;

use PDO;

class Connection extends \eq\db\ConnectionBase
{

    protected $driver = "mysql";
    protected $host;
    protected $dbname;
    protected $user;
    protected $pass;

    public function __construct($config)
    {
        parent::__construct($config);
        $this->host = Arr::getItem($config, "host", "localhost");
        $this->dbname = Arr::getItem($config, "dbname", null);
        $this->user = Arr::getItem($config, "user", "root");
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
