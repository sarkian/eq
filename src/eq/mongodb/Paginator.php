<?php

namespace eq\mongodb;

use eq\data\ModelBase;
use eq\data\PaginatorBase;

class Paginator extends PaginatorBase
{

    /**
     * @var string|Document
     */
    protected $classname;

    /**
     * @var Document
     */
    protected $model;

    /**
     * @param ModelBase|string $cname
     * @param array $condition
     * @param array $options
     */
    public function __construct($cname, array $condition = [], array $options = [])
    {
        parent::__construct($cname);
        $this->condition = $condition;
        $this->options = $options;
    }

    public function count()
    {
        if($this->_count === null) {
            $cls = $this->classname;
            $this->_count = $cls::count($this->condition, $this->options);
        }
        return $this->_count;
    }

    public function page($num)
    {
        $num > 0 or $num = 1;
        $cls = $this->classname;
        $opts = $this->options;
        $opts['limit'] = $this->model->page_size;
        $opts['skip'] = ($num - 1) * $this->model->page_size;
        return $cls::findAll($this->condition, $opts);
    }

}