<?php

namespace eq\widgets;

use eq\web\WidgetBase;

class PaginatorBase extends WidgetBase
{

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    protected $count;
    protected $current;
    protected $limit;

    public function __construct($count, $current = 1, $limit = 9)
    {
        $current > 0 or $current = 1;
        $this->count = $count;
        $this->current = $current;
        $this->limit = $limit;
    }

    protected function visiblePages()
    {
        if($this->count <= $this->limit)
            return range(1, $this->count);
        else {
            $start = $this->current - (int) floor($this->limit / 2);
            $start > 0 or $start = 1;
            if($start + $this->limit - 1 > $this->count)
                $start = $this->count - $this->limit + 1;
            return range($start, $start + $this->limit - 1);
        }
    }

    protected function pageData($num)
    {
        return [
            'url' => $this->pageUrl($num),
            'anchor' => $this->pageAnchor($num),
            'current' => $this->pageCurrent($num),
            'disabled' => $this->pageDisabled($num),
        ];
    }

    protected function pageUrl($num)
    {
        $params = ['page' => $num] + $_GET;
        return "?".implode("&", array_map(function($k, $v) {
            return $k."=".urlencode($v);
        }, array_keys($params), array_values($params)));
    }

    protected function pageAnchor($num)
    {
        return $num;
    }

    protected function pageDisabled($num)
    {
        return false;
    }

    protected function pageCurrent($num)
    {
        return (int) $num === (int) $this->current;
    }

} 