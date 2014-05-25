<?php

namespace eq\db;

use eq\base\Object;
use eq\helpers\Arr;
use eq\base\InvalidConfigException;
use eq\base\InvalidCallException;
use eq\base\LoaderException;

use PDO;
use PDOException;

/**
 * @property string name
 * @property PDO pdo
 * @property Schema schema
 * @method Query select(mixed $cols)
 * @method Query update(string $table, array $cols)
 * @method Query insert(string $table, array $cols)
 * @method Query from(mixed $tables)
 * @method Query delete(string $table, mixed $condition, array $params = [])
 * @method Query where(string $condition, array $params = [], string $glue = "AND")
 */
abstract class ConnectionBase extends Object
{

    protected $name;
    protected $config;
    protected $driver;
    protected $charset;
    protected $pdo = null;
    protected $schema = null;

    public function __construct($name, $config)
    {
        $this->name = $name;
        $this->config = $config;
        $this->charset = Arr::getItem($config, "charset", null);
    }

    public function __call($name, $args)
    {
        $q = $this->createQuery();
        if(method_exists($q, $name))
            return call_user_func_array([$q, $name], $args);
        else
            throw new InvalidCallException("Undefined method: $name");
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPdo()
    {
        $this->open();
        return $this->pdo;
    }

    public function getSchema()
    {
        if(!$this->schema)
            $this->schema = $this->createSchema();
        return $this->schema;
    }

    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public static function create($name, $config)
    {
        $driver = Arr::getItem($config, "driver", null);
        if(!$driver)
            throw new InvalidConfigException("Missing parameter: driver");
        $cname = 'eq\db\\'.$driver.'\Connection';
        try {
            $conn = new $cname($name, $config);
            return $conn;
        } catch(LoaderException $e) {
            throw new DbException("Unknown driver: $driver");
        }
    }

    public function open()
    {
        if($this->pdo)
            return;
        try {
            $this->createPDOInstance();
            $this->initConnection();
        } catch(PDOException $e) {
            throw new SQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function close()
    {
        if($this->pdo !== null)
            $this->pdo = null;
    }

    protected function createPDOInstance()
    {
        $this->pdo = new PDO($this->createDSN());
    }

    protected function initConnection()
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if($this->charset !== null && in_array($this->driver,
                ["pgsql", "mysql", "mysqli", "cubrid"])
        ) {
            $this->pdo->exec("SET NAMES ".$this->pdo->quote($this->charset));
        }
    }

    protected function createQuery()
    {
        return new Query($this);
    }

    protected function createSchema()
    {
        return new Schema($this);
    }

    abstract protected function createDSN();

}
