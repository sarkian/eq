<?php

namespace eq\base;

use EQ;

class WrapContainer implements \Iterator, \Countable
{

    protected $data = [];
    protected $wrapped_data = [];
    protected $pos = 0;
    protected $wrap_class;

    public function __construct(array $data, $wrap_class)
    {
        EQ::assert(Loader::classExists($wrap_class));
        $this->data = array_values($data);
        $this->wrap_class = $wrap_class;
    }

    public function current()
    {
        if(!isset($this->wrapped_data[$this->pos]))
            $this->wrapped_data[$this->pos] = new $this->wrap_class($this->data[$this->pos]);
        return $this->wrapped_data[$this->pos];
    }

    public function next()
    {
        ++$this->pos;
    }

    public function key()
    {
        return $this->pos;
    }

    public function valid()
    {
        return isset($this->data[$this->pos]);
    }

    public function rewind()
    {
        $this->pos = 0;
    }

    public function count()
    {
        return count($this->data);
    }
}