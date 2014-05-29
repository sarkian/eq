<?php

namespace eq\base;

use eq\helpers\Arr;

class CacheObject
{

    protected $loaded_data = [];
    protected $data = [];

    public function __construct($data)
    {
        $this->loaded_data = $data;
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
        return $this->data = $data;
    }

    public function getValue($key, $default = null)
    {
        return Arr::getItem($this->data, $key, $default);
    }

    public function setValue($key, $value)
    {
        Arr::setItem($this->data, $key, $value);
    }

    public function valueExists($key)
    {
        return Arr::itemExists($this->data, $key);
    }

    public function unsetValue($key)
    {
        Arr::unsetItem($this->data, $key);
    }

    public function isModified()
    {
        return $this->data !== $this->loaded_data;
    }

}
