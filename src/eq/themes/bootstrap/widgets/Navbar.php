<?php

namespace eq\themes\bootstrap\widgets;

use eq\datatypes\Bool;
use eq\web\html\Html;
use eq\widgets\NavbarBase;
use EQ;

/**
 * @property string nav_class
 * @property string collapse_id
 * @property string brand_title
 * @property string brand_link
 */
class Navbar extends NavbarBase
{

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    protected $fixed_padding = "70px";

    public function getNavClass()
    {
        $classes = ["navbar", "navbar-default"];
        $fixed = $this->attr("fixed");
        if($fixed !== false) {
            is_string($fixed) or $fixed = "top";
            $classes[] = "navbar-fixed-$fixed";
            if($fixed === "top" || $fixed === "bottom")
                EQ::app()->client_script->addCss("body {padding-$fixed: {$this->fixed_padding};}");
        }
        return implode(" ", $classes);
    }

    public function getCollapseId()
    {
        return "navbar-collapse";
    }

    public function getBrandTitle()
    {
        return $this->attr("brand_title");
    }

    public function getBrandLink()
    {
        return $this->attr("brand_link");
    }

    public function render()
    {
        return $this->renderView("main", ['bar' => $this]);
    }

    public function renderItem($item)
    {
        if(!is_array($item)) {
            switch($item) {
                case "#divider":
                    return Html::tag("li", ['class' => "divider"], "");
                default:
                    return "";
            }
        }
        $anchor = "";
        if(isset($item['icon']) && $item['icon'])
            $anchor .= Html::tag("span", ['class' => $item['icon']], "");
        if(isset($item['title']) && $item['title'])
            $anchor .= $anchor ? "\n".$item['title'] : $item['title'];
        if(!isset($item['link']))
            $item['link'] = isset($item['route'])
                ? EQ::app()->createUrl($item['route']) : "#";
        $li_class = [];
        if($this->isItemActive($item))
            $li_class[] = "active";
        $link_opts = isset($item['tooltip']) ? ['title' => $item['tooltip']] : [];
        if(isset($item['items']) && is_array($item['items']) && $item['items']) {
            $anchor .= Html::tag("b", ['class' => "caret"], "");
            $li_contents = Html::link($anchor, $item['link'], [
                'class' => "dropdown-toggle",
                'data-toggle' => "dropdown",
            ] + $link_opts);
            $li_class[] = "dropdown";
            $contents = [];
            foreach($item['items'] as $i) {
                $contents[] = $this->renderItem($i);
                if(!in_array("active", $li_class) && $this->isItemActive($i))
                    $li_class[] = "active";
            }
            $li_contents .= Html::tag("ul", ['class' => "dropdown-menu"], implode("\n", $contents));
        }
        else
            $li_contents = Html::link($anchor, $item['link'], $link_opts);
        return Html::tag("li", ['class' => $li_class], $li_contents);
    }

} 