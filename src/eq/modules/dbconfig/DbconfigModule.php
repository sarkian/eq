<?php

namespace eq\modules\dbconfig;

use EQ;
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

    /**
     * @var ConnectionBase $db
     */
    protected $db;
    protected $table;
    protected $data = [];
    protected $changed = [];
    protected $created = [];
    protected $removed = [];

    protected static function preInit()
    {
        self::instance(true)->init();
    }

    protected function init()
    {
        if(self::$_initialized)
            return;
        self::$_initialized = true;
        $this->db = EQ::app()->db($this->config("db", "main"));
        $this->table = $this->config("table", "config");
        $data = $this->executeQuery($this->db->select(["name", "value"])->from($this->table))
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach($data as $name => $value) {
            $value = unserialize($value);
            $this->data[$name] = $value;
            EQ::app()->configWrite($name, $value);
        }
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
        if(isset($this->data[$name]) && $this->data[$name] !== $value)
            $this->changed[$name] = $value;
        elseif(!isset($this->data[$name]))
            $this->created[$name] = $value;
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
            EQ::app()->configWrite($key, null);
        }
        EQ::log("REMOVE");
    }

    public function commit()
    {
        if(!$this->changed && !$this->created && !$this->removed)
            return;
        $this->db->beginTransaction();
        foreach($this->changed as $name => $value)
            $this->executeQuery($this->db->update($this->table, ['value' => serialize($value)])
                ->where(['name' => $name]));
        foreach($this->created as $name => $value)
            $this->executeQuery($this->db->
                insert($this->table, ['name' => $name, 'value' => serialize($value)]));
        foreach($this->removed as $name)
            $this->executeQuery($this->db->delete($this->table, ['name' => $name]));
        $this->db->commit();
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

}