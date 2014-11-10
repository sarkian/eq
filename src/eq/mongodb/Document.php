<?php

namespace eq\mongodb;

use EQ;
use eq\data\ModelBase;
use eq\datatypes\DataTypeBase;
use eq\helpers\Arr;
use eq\helpers\Str;
use MongoId;

/**
 * @property int _id
 * @property int id
 * @property string collection_name
 */
abstract class Document extends ModelBase
{

    /**
     * @var \MongoDB
     */
    protected $db;

    /**
     * @var \MongoCollection
     */
    protected $collection;

    protected $__id = null;

    public function __construct($scenario = null)
    {
        $this->db = EQ::app()->mongodb($this->db_name);
        $this->collection = $this->db->selectCollection($this->collection_name);
        parent::__construct($scenario);
    }

    /**
     * @param array $condition
     * @param array $options
     * @return \eq\data\Provider|static[]
     */
    public static function findAll(array $condition = [], array $options = [])
    {
        $model = static::i();
        $res = $model->selectQuery($model->loaded_fieldnames, $condition);
        if($options)
            $model->setOptions($res, $options);
        return static::provider(iterator_to_array($res));
    }

    public static function count(array $condition = [], array $options = [])
    {
        $limit = isset($options['limit']) ? $options['limit'] : 0;
        $skip = isset($options['skip']) ? $options['skip'] : 0;
        return static::i()->collection->count($condition, $limit, $skip);
    }

    public static function exists(array $condition, array $options = [])
    {
        return (bool) static::count($condition, $options);
    }

    public static function selectPks(array $condition = [], array $options = [])
    {
        $model = static::i();
        $res = $model->selectQuery([$model->pk], $condition);
        if($options)
            $model->setOptions($res, $options);
        $res = array_column(iterator_to_array($res), $model->pk);
        if(isset($options['cast']) && !$options['cast'])
            return $res;
        return array_map([$model->fieldType($model->pk), "fromDb"], $res);
    }

    public static function selectCols(array $cols, array $condition = [], array $options = [])
    {
        $model = static::i();
        $res = $model->selectQuery($cols, $condition);
        if($options)
            $model->setOptions($res, $options);
        $res = iterator_to_array($res);
        if(isset($options['cast']) && !$options['cast'])
            return $res;
        $cols = array_filter($cols, [$model, "fieldExists"]);
        return array_map(function ($row) use ($cols, $model) {
            foreach($row as $n => $v)
                $row[$n] = $model->typeToDb($n, $v);
            return $row;
        }, $res);
    }

    public static function paginator(array $condition = [], array $options = [])
    {
        return new Paginator(get_called_class(), $condition, $options);
    }

    public static function findRelatedAll(array $condition, array $sort = [])
    {
        $opts = [];
        if($sort) {
            $name = array_keys($sort)[0];
            $ord = strtolower(array_values($sort)[0]);
            $opts['sort'] = [$name => $ord === "desc" ? -1 : 1];
        }
        return static::findAll($condition, $opts);
    }

    public static function countRelated(array $condition)
    {
        return static::count($condition);
    }

    public function getCollectionName()
    {
        return Str::method2var(Str::classBasename(get_called_class())) . "s";
    }

    public function getPk()
    {
        return "_id";
    }

    public function setId($value)
    {
        $this->data['_id'] = $this->__id = $value;
    }

    public function getId()
    {
        return isset($this->data['_id']) ? $this->data['_id']
            : (isset($this->data['id']) ? $this->data['id'] : $this->__id);
    }

    public function field($name, $throw = true, $default = null)
    {
        return ($name === "_id" && !isset($this->fields['_id']) || ($name === "id" && !isset($this->fields['id']))) ? [
            'type' => "str",
            'default' => null,
            'save' => false,
        ] : parent::field($name, $throw, $default);
    }

    public function typeToDb($fieldname, $value)
    {
        $type = $this->fieldTypename($fieldname);
        if(($fieldname === "_id" && ($type === "str" || $type === "string")) || $type === "mongoid")
            return \eq\datatypes\Mongoid::toDb($value);
//            return is_object($value) && $value instanceof MongoId ? $value : new MongoId($value);
        elseif($type === "arr" || $type === "array")
            return (array) $value;
        elseif($type === "obj" || $type === "object")
            return (object) $value;
        elseif($type === "bool" || $type === "boolean")
            return (bool) $value;
        else
            return parent::typeToDb($fieldname, $value);
    }

    public function typeFromDb($fieldname, $value)
    {
        if($fieldname === "_id")
            return (string) $value;
        $type = $this->fieldTypename($fieldname);
        if($type === "arr" || $type === "array")
            return (array) $value;
        elseif($type === "obj" || $type === "object")
            return (object) $value;
        elseif($type === "bool" || $type === "boolean")
            return (bool) $value;
        else
            return parent::typeFromDb($fieldname, $value);
    }

    public function fieldsToSave()
    {
        return $this->saved_fieldnames;
    }
    
    protected function insertQuery(array $cols)
    {
        $cols = $this->processCondition($cols);
        $res = $this->collection->insert($cols);
        if(!$res || !isset($res['ok']) || !$res['ok'])
            return false;
        $this->__id = $cols['_id'];
        return true;
    }

    protected function updateQuery(array $cols, $condition)
    {
        $res = $this->collection->update($this->processCondition($condition), $this->processCondition($cols));
        if(!$res || !isset($res['ok']) || !$res['ok'])
            return false;
        return true;
    }

    protected function selectQuery(array $cols, $condition, array $options = [])
    {
        $fields = array_combine($cols, array_fill(0, count($cols), true));
        $res = $this->collection->find($this->processCondition($condition), $fields);
        if($options)
            $this->setOptions($res, $options);
        return $res;
    }

    protected function deleteQuery($condition)
    {
        $res = $this->collection->remove($this->processCondition($condition));
        if(!$res || !isset($res['ok']) || !$res['ok'])
            return false;
        return true;
    }

    protected function lastInsertId()
    {
        return (string) $this->__id;
    }

    protected function pkCondition($operator = null)
    {
        $pk = $this->{$this->pk};
        $value = $this->typeToDb($this->pk, $pk);
        $condition = $operator === null ? $value : [$operator => $value];
        return [$this->pk => $condition];
    }

    protected function validateUnique($fields)
    {
        $condition = ['$or' => []];
        foreach($fields as $name => $value)
            $condition['$or'][] = [$name => $value];
        if($this->loaded_data) {
            $condition = array_merge($condition, $this->pkCondition('$ne'));
        }
        $res = $this->collection->find($condition);
        if(!$res->count())
            return;
        foreach($res as $item) {
            foreach($item as $iname => $ivalue) {
                $ivalue = $this->typeFromDb($iname, $ivalue);
                if(isset($fields[$iname]) && $fields[$iname] === $ivalue)
                    $this->addError("unique", $iname);
            }
        }
    }

    protected function setOptions(\MongoCursor &$cursor, array $options)
    {
        if(isset($options['limit']))
            $cursor = $cursor->limit($options['limit']);
        if(isset($options['skip']))
            $cursor = $cursor->skip($options['skip']);
        if(isset($options['sort']))
            $cursor = $cursor->sort($options['sort']);
    }

    protected function processCondition($condition)
    {
        if(is_array($condition)) {
            if(isset($condition['id'])) {
                $condition['_id'] = $condition['id'];
                unset($condition['id']);
            }
            foreach($condition as $name => $value) {
                if(strncmp($name, '$', 1))
                    $condition[$name] = $this->typeToDb($name, $value);
            }
        }
        return $condition;
    }

}