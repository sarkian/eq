<?php
/**
 * Last Change: 2014 Mar 18, 12:47
 */

namespace eq\db;

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
        if(!is_array($cols)) {
            $cols = preg_split("/\s*,\s*/", trim($cols),
                        -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach($cols as $i => $col)
            $cols[$i] = $this->db->schema->quoteColumnName($col);
        $this->_query[] = "SELECT ".implode(",", $cols);
        return $this;
    }

    public function update($table, $cols)
    {
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
            $tables = preg_split("/\s*,\s*/", trim($tables),
                    -1, PREG_SPLIT_NO_EMPTY);
        foreach($tables as $i => $table)
            $tables[$i] = $this->db->schema->quoteTableName($table);
        $this->_query[] = "FROM ".implode(",", $tables);
        return $this;
    }

    public function delete($table, $condition, $params = [])
    {
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

    public function query()
    {
        $stmt = $this->buildStatement();
        try {
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
        }
        catch(PDOException $e) {
            throw new SQLException($e->getMessage(), $e->getCode(), $e);
        }
        return $stmt;
    }

    public function buildStatement($query = null, $params = null)
    {
        // $query or $query = $this->buildQuery();
        $query or $query = implode(" ", $this->_query);
        // echo $query."\n";
        // exit;
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
