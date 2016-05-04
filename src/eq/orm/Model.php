<?php

namespace eq\orm;

use EQ;
use eq\base\TEvent;
use eq\data\ModelBase;
use eq\db\ConnectionBase;
use eq\db\mysql\Schema;
use eq\db\Query;
use eq\db\SQLException;
use eq\helpers\Str;
use PDO;

/**
 * @property string table_name
 */
abstract class Model extends ModelBase
{

    use TEvent;

    protected $_create_table = false;

    protected $scenario = "default";

    /**
     * @var ConnectionBase
     */
    protected $db;

    public function __construct($scenario = null, $args = [])
    {
        $this->db = EQ::app()->db($this->db_name);
        parent::__construct($scenario, $args);
    }

    /**
     * @param string $condition
     * @param array $params
     * @param array $options
     * @return \eq\data\Provider|static[]
     * @throws SQLException
     * @throws \Exception
     */
    public static function findAll($condition = "1", array $params = [], array $options = [])
    {
        $model = static::i();
        $res = $model->executeQuery($model->createQuery()->select($model->loaded_fieldnames)
            ->from($model->table_name)->where($condition, $params)->setOptions($options));
        return static::provider($res->fetchAll());
    }

    public static function count($condition = "1", array $params = [], array $options = [])
    {
        $model = static::i();
        return (int) $model->executeQuery($model->createQuery()->select("COUNT(*)")
            ->from($model->table_name)->where($condition, $params)
            ->setOptions($options))->fetchColumn();
    }

    public static function exists($condition, array $params = [], array $options = [])
    {
        return (bool) static::count($condition, $params, $options);
    }

    public static function selectPks($condition = "1", array $params = [], array $options = [])
    {
        $model = static::i();
        $pks = $model->executeQuery($model->createQuery()->select([$model->pk])
            ->from($model->table_name)->where($condition, $params)
            ->setOptions($options))->fetchAll(PDO::FETCH_COLUMN);
        if(isset($options['cast']) && !$options['cast'])
            return $pks;
        return array_map([$model->fieldType($model->pk), "fromDb"], $pks);
    }

    public static function selectCols(array $cols, $condition = "1", array $params = [], array $options = [])
    {
        $model = static::i();
        $res = $model->executeQuery(
            $model->createQuery()->select($cols)->from($model->table_name)->where($condition, $params)
                ->setOptions($options))->fetchAll();
        if(isset($options['cast']) && !$options['cast'])
            return $res;
        $cols = array_filter($cols, [$model, "fieldExists"]);
        return array_map(function($row) use($cols, $model) {
            foreach($row as $n => $v)
                $row[$n] = $model->typeToDb($n, $v);
            return $row;
        }, $res);
    }

    public static function paginator($condition = "1", array $params = [], array $options = [])
    {
        return new Paginator(get_called_class(), $condition, $params, $options);
    }

    public static function findRelatedAll(array $condition, array $sort = [])
    {
        $opts = [];
        if($sort)
            $opts['order'] = $sort;
        return static::findAll($condition, [], $opts);
    }

    public static function countRelated(array $condition)
    {
        return static::count($condition);
    }

    public function getTableName()
    {
        return Str::method2var(Str::classBasename(get_called_class())) . "s";
    }

    public function getRules()
    {
        $fields = $this->fields;
        $pk = $this->pk;
        if($pk) {
            $pk_type = $this->fieldSql($pk, $this->typeSqlType($pk));
            if($pk_type === Schema::TYPE_PK || $pk_type === Schema::TYPE_BIGPK)
                unset($fields[$pk]);
        }
        return [
            'default' => [
                'change' => array_keys($fields),
            ],
        ];
    }

    public function fieldSql($name, $default = Schema::TYPE_TINYSTRING)
    {
        return $this->fieldProperty($name, "sql", $default);
    }

    public function typeSqlType($fieldname)
    {
        $type = $this->fieldType($fieldname);
        return $type::sqlType($this->db->driver);
    }

    public function createTable()
    {
        $cols = [];
        foreach($this->fieldnames as $field) {
            if($this->isLoad($field) || $this->isSave($field))
                $cols[$field] = $this->fieldSql($field, $this->fieldType($field));
        }
        $pk = isset($cols[$this->pk]) ? $this->pk : null;
        $this->db->createTable($this->table_name, $cols, $pk)->execute();
    }

    protected function insertQuery(array $cols)
    {
        return $this->executeQuery($this->createQuery()
            ->insert($this->table_name, $this->processCondition($cols)))->rowCount();
    }

    protected function updateQuery(array $cols, $condition)
    {
        return $this->executeQuery($this->createQuery()
                ->update($this->table_name, $this->processCondition($cols))
                ->where($this->processCondition($condition))
        )->rowCount();
    }

    protected function selectQuery(array $cols, $condition, array $options = [])
    {
        return $this->executeQuery(
            $this->createQuery()->select($cols)->from($this->table_name)
                ->where($this->processCondition($condition))->setOptions($options)
        )->fetchAll();
    }

    protected function deleteQuery($condition)
    {
        return $this->executeQuery(
            $this->createQuery()->delete($this->table_name, $this->processCondition($condition))
        )->rowCount();
    }

    protected function lastInsertId()
    {
        $this->db->pdo->lastInsertId($this->pk);
    }

    protected function validateUnique($fields)
    {
        $cols = [];
        foreach($fields as $name => $value)
            $cols[$name] = $this->typeToDb($name, $value);
        $condition = $this->db->schema->buildCondition($cols, "=", "OR");
        if($this->loaded_data) {
            $condition = "(" . $condition . ") AND " . $this->pkCondition("<>");
        }
        $res = $this->executeQuery(
            $this->createQuery()->select(array_keys($fields))
                ->from($this->table_name)->where($condition, [], "OR")
        );
        foreach($res->fetchAll() as $item) {
            foreach($item as $iname => $ivalue) {
                $ivalue = $this->typeFromDb($iname, $ivalue);
                if(isset($fields[$iname]) && $fields[$iname] === $ivalue)
                    $this->addError("unique", $iname);
            }
        }
    }

    protected function pkCondition($operator = "=")
    {
        return $this->db->schema->buildCondition(
            [$this->pk => $this->typeToDb($this->pk, $this->{$this->pk})],
            $operator
        );
    }

    /**
     * @return Query
     */
    protected function createQuery()
    {
        $types = [];
        foreach($this->fieldnames as $name)
            $types[$name] = $this->fieldSql($name, $this->typeSqlType($name));
        return $this->db->createQuery()->setBindTypes($types);
    }

    protected function executeQuery(Query $query)
    {
        try {
            return $query->execute();
        }
        catch(SQLException $e) {
            if(!EQ::app()->config("db.auto_create_table", false) || $this->_create_table)
                throw $e;
            if($this->db->tableExists($this->table_name))
                throw $e;
            $this->_create_table = true;
            $this->createTable();
            return $query->execute();
        }
    }

}
