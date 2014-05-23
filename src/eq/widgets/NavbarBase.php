<?php

namespace eq\widgets;

use EQ;
use eq\web\html\Html;
use eq\web\WidgetBase;

// TODO: links ordering
/**
 * @property string active_page
 * @property array items
 */
abstract class NavbarBase extends WidgetBase
{

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    public function getItems()
    {
        return $this->attr("items");
    }

    public function getActivePage()
    {
        return EQ::app()->controller_name.".".EQ::app()->action_name;
    }

    public function isItemActive($item)
    {
        if(is_array($item)) {
            if(isset($item['pattern']) && $item['pattern'])
                $pattern = $item['pattern'];
            elseif(isset($item['route']) && $item['route'])
                $pattern = $item['route'];
            else
                return false;
        }
        else
            $pattern = $item;
        return fnmatch($pattern, $this->active_page);
    }

    public function render()
    {
        $out = [];
        foreach($this->items as $item) {
            if($this->isItemVisible($item))
                $out[] = $this->renderItem($item);
        }
        return implode("\n", $out);
    }

    public function renderItem($item)
    {
        if(!isset($item['link']) || !$item['link']) {
            if(isset($item['route']) && $item['route'])
                $item['link'] = EQ::app()->createUrl($item['route']);
            else
                $item['link'] = "#";
        }
        $opts = $this->isItemActive($item) ? ['class' => "active"] : [];
        return Html::tag("li", $opts, Html::tag("a", $opts + [
            'href' => $item['link'],
        ], $item['title']));
    }

    public function isItemVisible($item)
    {
        if(!isset($item['perms']) || !$item['perms'] || $item['perms'] === "all")
            return true;
        $perms = explode(",", $item['perms']);
        array_walk($perms, function(&$str) {
            $str = strtolower(trim($str, " \n\r\t"));
        });
        return in_array(EQ::app()->user->status, $perms);
    }

}
