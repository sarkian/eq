<?php

namespace eq\base;


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
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function setValue($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function valueExists($key)
    {
        return isset($this->data[$key]);
    }

    public function unsetValue($key)
    {
        unset($this->data[$key]);
    }

    public function isModified()
    {
        return $this->data !== $this->loaded_data;
    }

}
