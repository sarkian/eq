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
    }

    public static function findAll(array $condition = [], array $options = [])
    {
        $model = static::i();
        $res = $model->selectQuery($model->loaded_fieldnames, $condition);
        if($options)
            $model->setOptions($res, $options);
        return static::createProvider(iterator_to_array($res));
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
    
    public function getCollectionName()
    {
        return Str::method2var(Str::classBasename(get_called_class())) . "s";
    }

    public function getPk()
    {
        return "_id";
    }

    public function getId()
    {
        return isset($this->data['_id']) ? $this->data['_id'] : $this->__id;
    }

    public function field($name, $throw = true, $default = null)
    {
        return $name === "_id" && !isset($this->fields['_id']) ? [
            'type' => "str",
            'default' => null,
            'save' => false,
        ] : parent::field($name, $throw, $default);
    }

    public function typeToDb($fieldname, $value)
    {
        if($fieldname === "_id")
            return is_object($value) && $value instanceof MongoId ? $value : new MongoId($value);
        $type = $this->fieldTypename($fieldname);
        if($type === "arr" || $type === "array")
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
    
    public function itemSet($name, $value, $unique = false)
    {
        Arr::setItem($this->data, $name, $value, $unique);
    }

    public function itemGet($name, $default = null)
    {
        return Arr::getItem($this->data, $name, $default);
    }

    public function itemUnset($name)
    {
        Arr::unsetItem($this->data, $name);
    }

    protected function insertQuery(array $cols)
    {
        $res = $this->collection->insert($cols);
        if(!$res || !isset($res['ok']) || !$res['ok'])
            return false;
        $this->__id = $cols['_id'];
        return true;
    }

    protected function updateQuery(array $cols, $condition)
    {
        $res = $this->collection->update($condition, $cols);
        if(!$res || !isset($res['ok']) || !$res['ok'])
            return false;
        return true;
    }

    protected function selectQuery(array $cols, $condition, array $options = [])
    {
        $fields = array_combine($cols, array_fill(0, count($cols), true));
        $res = $this->collection->find($condition, $fields);
        if($options)
            $this->setOptions($res, $options);
        return $res;
    }

    protected function deleteQuery($condition)
    {
        $res = $this->collection->remove($condition);
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
        $value = $this->typeToDb($this->pk, $this->{$this->pk});
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

}