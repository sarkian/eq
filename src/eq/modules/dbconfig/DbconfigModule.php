<?php

namespace eq\modules\dbconfig;

use EQ;
use eq\base\InvalidConfigException;
use eq\base\ModuleBase;
use eq\db\ConnectionBase;
use eq\db\Query;
use eq\db\SQLException;
use eq\modules\dbconfig\datatypes\Name;
use eq\modules\dbconfig\datatypes\Value;
use PDO;

// TODO: cache
class DbconfigModule extends ModuleBase
{

    private static $_initialized = false;

    private $create_table = false;

    protected $db_type;

    /**
     * @var ConnectionBase|\MongoDB
     */
    protected $db;

    protected $table;

    protected $use_json = true;

    /**
     * @var \MongoCollection
     */
    protected $collection;

    protected $data = [];
    protected $changed = [];
    protected $created = [];
    protected $removed = [];

    protected static function preInit()
    {
        self::inst(true)->init();
    }

    protected function init()
    {
        if(self::$_initialized)
            return;
        self::$_initialized = true;
        $this->use_json = $this->config("use_json", true);
        $this->db_type = strtolower($this->config("db_type", "sql"));
        if($this->db_type === "sql") {
            $this->db = EQ::app()->db($this->config("db_name"));
            $this->table = $this->config("table_name", "config");
            $data = $this->executeQuery($this->db->select(["name", "value"])->from($this->table))
                ->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach($data as $name => $value) {
                $value = $this->parse($value);
                $this->data[$name] = $value;
                EQ::app()->configWrite($name, $value);
            }
        }
        elseif($this->db_type === "mongo") {
            $this->db = EQ::app()->mongodb($this->config("db_name"));
            $this->collection = $this->db->selectCollection($this->config("collection_name", "config"));
            foreach($this->collection->find([], ["name", "value"]) as $rec) {
                if(!isset($rec['name'], $rec['value']) || !is_string($rec['name']) || !strlen($rec['name']))
                    continue;
                $this->data[$rec['name']] = $rec['value'];
                EQ::app()->configWrite($rec['name'], $rec['value']);
            }
        }
        else
            throw new InvalidConfigException("Invalid DB type: {$this->db_type}");
        EQ::app()->bind("config.save", [$this, "set"]);
        EQ::app()->bind("config.remove", [$this, "remove"]);
        EQ::app()->bind("shutdown", [$this, "commit"]);
    }

    public function getComponents()
    {
        return [
            'dbconfig' => $this,
        ];
    }

    public function get($name, $default = null)
    {
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    public function set($name, $value)
    {
        $k = array_search($name, $this->removed, true);
        if((isset($this->data[$name]) && $this->data[$name] !== $value) || $k !== false)
            $this->changed[$name] = $value;
        elseif(!isset($this->data[$name]))
            $this->created[$name] = $value;
        if($k !== false)
            unset($this->removed[$k]);
        $this->data[$name] = $value;
        EQ::app()->configWrite($name, $value);
    }

    public function remove($name)
    {
        if(!$name)
            return;
        $expr = "/^".preg_quote($name, "/")."/";
        foreach($this->data as $key => $value) {
            if(!preg_match($expr, $key))
                continue;
            if(!in_array($key, $this->removed))
                $this->removed[] = $key;
            unset($this->data[$key]);
            unset($this->changed[$key]);
            unset($this->created[$key]);
            EQ::app()->configWrite($key, null);
        }
    }

    public function commit()
    {
        if(!$this->changed && !$this->created && !$this->removed)
            return;
        if($this->db_type === "sql") {
            $this->db->beginTransaction();
            foreach($this->changed as $name => $value)
                $this->executeQuery($this->db->update($this->table, ['value' => $this->stringify($value)])
                    ->where(['name' => $name]));
            foreach($this->created as $name => $value)
                $this->executeQuery($this->db
                    ->insert($this->table, ['name' => $name, 'value' => $this->stringify($value)]));
            foreach($this->removed as $name)
                $this->executeQuery($this->db->delete($this->table, ['name' => $name]));
            $this->db->commit();
        }
        elseif($this->db_type === "mongo") {
            foreach($this->changed as $name => $value)
                $this->collection->update(['name' => $name], ['name' => $name, 'value' => $value]);
            foreach($this->created as $name => $value)
                $this->collection->insert(['name' => $name, 'value' => $value]);
            foreach($this->removed as $name)
                $this->collection->remove(['name' => $name]);
        }
        $this->changed = [];
        $this->created = [];
        $this->removed = [];
    }

    public function __destruct()
    {
        $this->commit();
    }

    protected function executeQuery(Query $query)
    {
        try {
            return $query->execute();
        }
        catch(SQLException $e) {
            if(!EQ::app()->config("db.auto_create_table", false) || $this->create_table)
                throw $e;
            if($this->db->tableExists($this->table))
                throw $e;
            $this->create_table = true;
            $this->createTable();
            return $query->execute();
        }
    }

    protected function createTable()
    {
        $this->db->createTable($this->table, [
            'name' => Name::c(),
            'value' => Value::c(),
        ])->execute();
    }

    protected function stringify($value)
    {
        return $this->use_json ? json_encode($value) : serialize($value);
    }

    protected function parse($value)
    {
        return $this->use_json ? json_decode($value) : unserialize($value);
    }

}