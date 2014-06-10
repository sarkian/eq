<?php

namespace eq\db;

use EQ;
use eq\datatypes\DataTypeBase;
use PDO;
use PDOException;

class Query
{

    protected $db;

    protected $params = [];

    protected $_query = [];
    protected $bind_types = [];

    public function __construct(ConnectionBase $db)
    {
        $this->db = $db;
    }

    public function select($cols)
    {
        $this->_query = [];
        if(!is_array($cols))
            $cols = preg_split('/\s*,\s*/', trim($cols), -1, PREG_SPLIT_NO_EMPTY);
        foreach($cols as $i => $col)
            $cols[$i] = $this->db->schema->quoteColumnName($col);
        $this->_query[] = "SELECT ".implode(",", $cols);
        return $this;
    }

    public function update($table, $cols)
    {
        $this->_query = [];
        $lines = [];
        foreach($cols as $name => $value) {
            $lines[] = $this->db->schema->quoteColumnName($name)
                ."=".$this->db->schema->quoteValue($value);
        }
        $this->_query[] = "UPDATE ".$this->db->schema->quoteTableName($table)
            ." SET ".implode(",", $lines);
        return $this;
    }

    public function insert($table, $cols)
    {
        $this->_query = [];
        $names = [];
        $values = [];
        foreach($cols as $name => $value) {
            $names[] = $this->db->schema->quoteColumnName($name);
            $values[] = $this->db->schema->quoteValue($value);
        }
        $this->_query[] = "INSERT INTO "
            .$this->db->schema->quoteTableName($table)
            ." (".implode(",", $names).") VALUES(".implode(",", $values).")";
        return $this;
    }

    public function from($tables)
    {
        if(!is_array($tables))
            $tables = preg_split('/\s*,\s*/', trim($tables),
                -1, PREG_SPLIT_NO_EMPTY);
        foreach($tables as $i => $table)
            $tables[$i] = $this->db->schema->quoteTableName($table);
        $this->_query[] = "FROM ".implode(",", $tables);
        return $this;
    }

    public function delete($table, $condition, $params = [])
    {
        $this->_query = [];
        $this->_query[] = "DELETE FROM "
            .$this->db->schema->quoteTableName($table);
        $this->where($condition, $params);
        return $this;
    }

    public function where($condition, $params = [], $glue = "AND")
    {
        $this->_query[] = "WHERE ".$this->buildWhere($condition, $glue);
        $this->addParams($params);
        return $this;
    }

    public function tableExists($table)
    {
        $this->_query = [];
        $this->_query[] = "SELECT 1 FROM ".$this->db->schema->quoteTableName($table);
        return $this;
    }
    
    public function createTable($table, $columns, $options = null)
    {
        $this->_query = [];
        $cols = [];
        foreach($columns as $name => $type) {
            if(is_string($name))
                $cols[] = "\t".$this->db->schema->quoteColumnName($name)
                    ." ".$this->db->schema->columnType($type);
            else
                $cols[] = "\t".$type;
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".$this->db->schema->quoteTableName($table)
            ."(\n".implode(",\n", $cols)."\n)";
        $this->_query[] = is_null($options) ? $sql : "$sql $options";
        return $this;
    }

    public function execute()
    {
        $stmt = $this->buildStatement();
        try {
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
        }
        catch(PDOException $e) {
            throw new SQLException($e->getMessage(), $e->getCode(), $e);
        }
        EQ::app()->trigger("dbQuery", $this->db->name, $stmt->queryString);
        return $stmt;
    }

    public function buildStatement($query = null, $params = null)
    {
        $query or $query = implode(" ", $this->_query);
        $query = trim($query, " \r\n\t");
        if(!preg_match("/;$/", $query))
            $query .= ";";
        $stmt = $this->db->pdo->prepare($query);
        $params !== null or $params = $this->params;
        foreach($params as $name => $value)
            $stmt->bindValue(":$name", "$value",
                isset($this->bind_types[$name])
                    ? $this->bind_types[$name] : PDO::PARAM_STR);
        return $stmt;
    }

    protected function buildWhere($condition, $glue = "AND")
    {
        if(!is_array($condition))
            return $condition;
        $res = [];
        foreach($condition as $name => $value)
            $res[] = $this->db->schema->quoteColumnName($name)
                ."=".$this->db->schema->quoteValue($value);
        return implode(" $glue ", $res);
    }

    protected function addParams($params)
    {
        $this->params = array_merge($this->params, $params);
    }

}
