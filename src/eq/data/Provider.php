<?php

namespace eq\data;

use eq\base\InvalidCallException;
use eq\base\NotImplementedException;

class Provider implements \Iterator, \Countable, \ArrayAccess
{

    protected $class_name;
    protected $scenario;
    protected $pk;
    protected $data = [];
    protected $by_pk = [];
    protected $by_field = [];
    protected $pos = 0;

    /**
     * @param array|ModelBase[] $data
     * @param string|ModelBase $class_name
     * @param string $scenario
     * @throws \eq\base\InvalidArgumentException
     * @throws \eq\base\InvalidCallException
     */
    public function __construct(array $data, $class_name = null, $scenario = null, $args = [])
    {
        /**
         * @var array|ModelBase[] $data
         */
        $data = array_values($data);
        if(!$class_name) {
            if(!count($data))
                throw new InvalidCallException("Class name must be specified if data is empty");
            $item = array_shift($data);
            if(!is_object($item))
                throw new InvalidCallException(
                    "Data must be contains objects if class name not specified");
            $class_name = get_class($item);
            array_unshift($data, $item);
        }
        if(!is_subclass_of($class_name, 'eq\data\ModelBase'))
            throw new InvalidCallException("'$class_name' is not a subclass of eq\\data\\ModelBase");

        $model = new $class_name();
        if($model->pk /*&& isset($model->fields[$model->pk])*/)
            $this->pk = $model->pk;

        $this->class_name = $class_name;
        $this->scenario = $scenario;
        $this->data = [];
        $this->by_pk = [];
        $this->pos = 0;
        foreach($data as $i => $item) {
            if(is_object($item) && get_class($item) === $class_name)
                $obj = $item;
            else
                $obj = $class_name::i($scenario, $args)->applyLoaded($item);
            if($scenario)
                $obj->scenario = $scenario;
            $this->data[$i] = $obj;
//            if($obj->pk && isset($obj->fields[$obj->pk])) {
            if($this->pk) {
                $pk = $obj->fieldValue($obj->pk);
                if(is_int($pk) || (is_string($pk) && strlen($pk)))
                    $this->by_pk[$pk] = $i;
            }
        }
    }


    /**
     * @param string $field
     * @param mixed $value
     *
     * @return ModelBase|null
     */
    public function byField($field, $value) {
        if(!isset($this->by_field[$field])) {
            $this->by_field[$field] = [];
            foreach($this->data as $obj) {
                $this->by_field[$field][$obj->{$field}] = $obj;
            }
        }
        return isset($this->by_field[$field][$value]) ? $this->by_field[$field][$value] : null;
    }

    /**
     * @param mixed $pk
     * @return ModelBase|null
     */
    public function byPk($pk)
    {
        if(!isset($this->by_pk[$pk]))
            return null;
        $i = $this->by_pk[$pk];
        return isset($this->data[$i]) ? $this->data[$i] : null;
    }

    /**
     * @param array $pks
     * @return static
     */
    public function byPks(array $pks)
    {
        $res = [];
        foreach($pks as $pk) {
            $item = $this->byPk($pk);
            if($item)
                $res[] = $item;
        }
        $cname = get_called_class();
        return new $cname($res, $this->class_name, $this->scenario);
    }

    public function addByPk($obj, $update = false)
    {
        if(!$this->pk)
            return;
        $pk = $obj->{$this->pk};
        if(isset($this->by_pk[$pk])) {
            if(!$update)
                return;
            $i = $this->by_pk[$pk];
            $this->data[$i] = $obj;
        }
        else {
            $this->data[] = $obj;
            $this->by_pk[$pk] = array_keys($this->data, $obj, true)[0];
        }
    }

    public function unsetByPk($pk)
    {
        if(!isset($this->by_pk[$pk]))
            return;
        unset($this->data[$this->by_pk[$pk]]);
        unset($this->by_pk[$pk]);
    }
    
    public function getPks()
    {
        return array_keys($this->by_pk);
    }

    public function column($name)
    {
        return array_map(function($obj) use($name) {
            return $obj->{$name};
        }, $this->data);
    }

    public function columnByPk($name)
    {
        return array_map(function($i) use($name) {
            return isset($this->data[$i]) ? $this->data[$i]->{$name} : null;
        }, $this->by_pk);
    }

    public function map($fn)
    {
        return array_map($fn, $this->data);
    }

    public function reduce($fn, $initial = null)
    {
        return array_reduce($this->data, $fn, $initial);
    }
    
    public function walk($fn)
    {
        return array_walk($this->data, $fn);
    }

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type
     */
    public function current()
    {
        return $this->data[$this->pos];
    }

    /**
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored
     */
    public function next()
    {
        ++$this->pos;
    }

    /**
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure
     */
    public function key()
    {
        return $this->pos;
    }

    /**
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated
     * Returns true on success or false on failure
     */
    public function valid()
    {
        return isset($this->data[$this->pos]);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored
     */
    public function rewind()
    {
        $this->pos = 0;
    }

    /**
     * Whether a offset exists
     *
     * The return value will be casted to boolean if non-boolean was returned
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for
     * @return boolean true on success or false on failure
     */
    public function offsetExists($offset)
    {
        return is_int($offset) ? isset($this->data[$offset]) : false;
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve
     * @return mixed Can return all value types
     */
    public function offsetGet($offset)
    {
        return (is_int($offset) && isset($this->data[$offset])) ? $this->data[$offset] : null;
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to
     * @param mixed $value The value to set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if(is_int($offset) && $offset >= 0
                && is_object($value) && get_class($value) === $this->class_name)
            $this->data[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if(is_int($offset) && $offset >= 0 && isset($this->data[$offset]))
            unset($this->data[$offset]);
    }

    /**
     * Count elements of an object
     *
     * The return value is cast to an integer
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @return ModelBase[]
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * @return ModelBase|null
     */
    public function first()
    {
        return $this->data ? $this->data[min(array_keys($this->data))] : null;
    }

    /**
     * @return ModelBase|null
     */
    public function last()
    {
        return $this->data ? $this->data[max(array_keys($this->data))] : null;
    }

    // TODO: Relations
    public function loadRelations()
    {
        throw new NotImplementedException();
        $relations = $this->emptyModel()->relations;
        foreach($relations as $rel) {
            if($rel->isCustom())
                continue;

        }
    }

    /**
     * @param string $scenario
     * @return ModelBase
     */
    public function emptyModel($scenario = null)
    {
        $cname = $this->class_name;
        return new $cname($scenario !== null ? $scenario : $this->scenario);
    }

}