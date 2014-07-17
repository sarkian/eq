<?php

namespace eq\orm;

use eq\data\ModelBase;
use eq\data\PaginatorBase;

class Paginator extends PaginatorBase
{

    /**
     * @var string|Model
     */
    protected $classname;

    /**
     * @var Model
     */
    protected $model;

    protected $params;

    /**
     * @param ModelBase|string $cname
     * @param string $condition
     * @param array $params
     * @param array $options
     */
    public function __construct($cname, $condition = "1", array $params = [], array $options = [])
    {
        parent::__construct($cname);
        $this->condition = $condition;
        $this->params = $params;
        $this->options = $options;
    }

    public function count()
    {
        if($this->_count === null) {
            $cls = $this->classname;
            $this->_count = $cls::count($this->condition, $this->params, $this->options);
        }
        return $this->_count;
    }

    public function page($num)
    {
        $num > 0 or $num = 1;
        $cls = $this->classname;
        $opts = $this->options;
        $opts['limit'] = [
            ($num - 1) * $this->model->page_size,
            $this->model->page_size,
        ];
        return $cls::findAll($this->condition, $this->params, $opts);
    }

} 