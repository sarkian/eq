<?php

namespace eq\db;

use eq\datatypes\DataTypeBase;

// TODO: datatypes mappings
class Schema
{

    protected $db;
    protected $type_map = [];

    public function __construct(ConnectionBase $db)
    {
        $this->db = $db;
    }

    public function quoteValue($str)
    {
        if(is_null($str))
            return "null";
        if(is_int($str) || is_float($str))
            return $str;
        if(is_bool($str))
            return (int) $str;
		$this->db->open();
		if(($value = $this->db->pdo->quote($str)) !== false)
            return $value;
		else
            return "'".addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032")."'";
    }

    public function quoteTableName($name)
    {
        if(strpos($name, "(") !== false || strpos($name, "{{") !== false)
			return $name;
		if(strpos($name, ".") === false)
			return $this->quoteSimpleTableName($name);
		$parts = explode(".", $name);
		foreach($parts as $i => $part) {
			$parts[$i] = $this->quoteSimpleTableName($part);
		}
		return implode(".", $parts);
    }

    public function quoteColumnName($name)
    {
        if(strpos($name, "(") !== false || strpos($name, "[[") !== false
                    || strpos($name, "{{") !== false)
			return $name;
		if(($pos = strrpos($name, ".")) !== false) {
			$prefix = $this->quoteTableName(substr($name, 0, $pos)).".";
			$name = substr($name, $pos + 1);
        }
        else
			$prefix = "";
		return $prefix.$this->quoteSimpleColumnName($name);
    }

    public function quoteSimpleTableName($name)
    {
        return strpos($name, "'") !== false ? $name : "'".$name."'";
    }

    public function quoteSimpleColumnName($name)
    {
        return strpos($name, '"') !== false || $name === "*" ? $name : '"'.$name.'"';
    }

    public function buildCondition($condition, $operator = "=", $glue = "AND")
    {
        if(!is_array($condition))
            return $condition;
        $res = [];
        foreach($condition as $name => $value)
            $res[] = $this->quoteColumnName($name)
                .$operator.$this->quoteValue($value);
        return implode(" $glue ", $res);
    }
    
    public function columnType($type)
    {
        $parts = explode(" ", $type, 2);
        $opts = isset($parts[1]) ? " ".$parts[1] : "";
        $type = $parts[0];
        if(isset($this->type_map[$type]))
            return $this->type_map[$type].$opts;
        $cls = DataTypeBase::getClass($type);
        return $cls::sqlType($this->db->driver).$opts;
    }

}
