<?php

namespace eq\data;

use eq\base\InvalidArgumentException;

abstract class PaginatorBase
{

    /**
     * @var string|ModelBase
     */
    protected $classname;

    /**
     * @var ModelBase
     */
    protected $model;

    /**
     * @var int
     */
    protected $_count = null;

    protected $condition;
    protected $options;

    abstract public function count();
    abstract public function page($num);

    /**
     * @param string|ModelBase $cname
     * @throws \eq\base\InvalidArgumentException
     */
    public function __construct($cname)
    {
        $this->classname = $cname;
        $this->model = new $cname();
        if(!$this->model instanceof ModelBase)
            throw new InvalidArgumentException($this, __METHOD__, "cname", $cname);
    }
    
    public function pageCount()
    {
        return (int) ceil($this->count() / $this->model->page_size);
    }

    public function pageExists($num)
    {
        return $num <= $this->pageCount();
    }

}
