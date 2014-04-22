<?php
/**
 * Last Change: 2014 Apr 19, 19:14
 */

namespace eq\widgets;

use EQ;

abstract class NavbarBase extends \eq\web\WidgetBase
{

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    public function getLinks()
    {
        return [];
    }

    public function getActivePage()
    {
        return EQ::app()->controller_name.".".EQ::app()->action_name;
    }

    public function isLinkActive($name)
    {
        EQ::clog($name, $this->active_page);
        return fnmatch($name, $this->active_page);
    }

    public function render()
    {
        return $this->renderView("main", ['bar' => $this]);
    }

}
