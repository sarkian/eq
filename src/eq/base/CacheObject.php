<?php
/**
 * Last Change: 2014 Apr 23, 22:58
 */

namespace eq\base;

use eq\helpers\Arr;

class CacheObject
{

    protected $data = [];
    protected $modified = false;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        if(!is_array($data))
            $data = [];
        $this->data = $data;
        $this->modified = true;
    }

    public function isModified()
    {
        return $this->modified;
    }

    public function call($name, $value = null)
    {
        if(is_null($value))
            return Arr::getItem($this->data, $name, null);
        Arr::setItem($this->data, $name, $value);
        $this->modified = true;
    }

    public function get($name, $default = null)
    {
        return Arr::getItem($this->data, $name, $default);
    }

}
