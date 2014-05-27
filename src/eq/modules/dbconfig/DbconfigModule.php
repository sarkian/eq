<?php

namespace eq\modules\dbconfig;

use EQ;
use eq\base\ModuleBase;
use eq\db\ConnectionBase;
use PDO;

class DbconfigModule extends ModuleBase
{

    private static $_initialized = false;

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
        $data = $this->db->select(["name", "value"])->from($this->table)
            ->query()->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach($data as $name => $value) {
            $value = unserialize($value);
            $this->data[$name] = $value;
            EQ::app()->configWrite($name, $value);
        }
        $this->registerComponent("dbconfig", $this);
        EQ::app()->bind("shutdown", [$this, "commit"]);
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
            $this->db->update($this->table, ['value' => serialize($value)])
                ->where(['name' => $name])->query();
        foreach($this->created as $name => $value)
            $this->db->
                insert($this->table, ['name' => $name, 'value' => serialize($value)])->query();
        foreach($this->removed as $name)
            $this->db->delete($this->table, ['name' => $name])->query();
        $this->db->commit();
        $this->changed = [];
        $this->created = [];
        $this->removed = [];
    }

    public function __destruct()
    {
        $this->commit();
    }

}