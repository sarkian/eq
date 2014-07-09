<?php

namespace eq\db;

use EQ;
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

    public function setBindTypes(array $types)
    {
        $this->bind_types = $types;
        return $this;
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
    
    public function orderBy($ordering)
    {
        $parts = explode(" ", $ordering, 2);
        $colname = $this->db->schema->quoteColumnName(array_shift($parts));
        array_unshift($parts, $colname);
        $this->_query[] = "ORDER BY ".implode(" ", $parts);
        return $this;
    }

    public function limit($limit)
    {
        $this->_query[] = "LIMIT ".(int) $limit;
        return $this;
    }

    public function setOptions(array $options = [])
    {
        if(isset($options['order']))
            $this->orderBy($options['order']);
        if(isset($options['limit']))
            $this->limit($options['limit']);
        return $this;
    }

    public function tableExists($table)
    {
        $this->_query = [];
        $this->_query[] = "SELECT 1 FROM ".$this->db->schema->quoteTableName($table);
        return $this;
    }
    
    public function createTable($table, $columns, $pk = null, $options = null)
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
        if($pk && !in_array(Schema::TYPE_PK, $columns) && !in_array(Schema::TYPE_BIGPK, $columns))
            $cols[] = "\tPRIMARY KEY (".$this->db->schema->quoteColumnName($pk).")";
        $sql = "CREATE TABLE IF NOT EXISTS ".$this->db->schema->quoteTableName($table)
            ."(\n".implode(",\n", $cols)."\n)";
        $this->_query[] = is_null($options) ? $sql : "$sql $options";
        return $this;
    }

    public function execute()
    {
        $stmt = $this->buildStatement();
        $query = $this->interpolateQuery($stmt->queryString);
        EQ::app()->trigger("dbQuery", $this->db->name, $query);
        try {
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
        }
        catch(PDOException $e) {
            throw new SQLException($e->getMessage(), $e->getCode(), $query, $e);
        }
        return $stmt;
    }

    public function buildStatement($query = null, array $params = null)
    {
        $query or $query = implode(" ", $this->_query);
        $query = trim($query, " \r\n\t");
        if(!preg_match("/;$/", $query))
            $query .= ";";
        $stmt = $this->db->pdo->prepare($query);
        if($params !== null)
            $this->params = $params;
        foreach($this->params as $name => $value) {
            $stmt->bindValue(":$name", $value,
                isset($this->bind_types[$name])
                    ? $this->db->schema->bindType($this->bind_types[$name]) : PDO::PARAM_STR);
        }
        return $stmt;
    }

    protected function buildWhere($condition, $glue = "AND")
    {
        if(!is_array($condition))
            return $condition;
        if(empty($condition))
            return "1";
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

    protected function interpolateQuery($query)
    {
        foreach($this->params as $name => $value)
            $query = str_replace(":$name", $this->db->schema->quoteValue($value), $query);
        return $query;
    }

}
