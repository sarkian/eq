<?php

namespace eq\themes\bootstrap\widgets;

use eq\widgets\PaginatorBase;

class Paginator extends PaginatorBase
{

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    public function render()
    {
        $pages = $this->visiblePages();
        $links = array_map([$this, "pageData"], $pages);
        return $this->renderView("main", [
            'first' => in_array(1, $pages) ? null : $this->pageData(1),
            'last' => in_array($this->count, $pages) ? null : $this->pageData($this->count),
            'links' => $links,
        ]);
    }

}