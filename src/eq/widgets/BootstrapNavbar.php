<?php
/**
 * Last Change: 2014 Apr 09, 07:41
 */

namespace eq\widgets;

class BootstrapNavbar extends NavbarBase
{

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    public function getNavClass()
    {
        return "navbar navbar-default";
    }

    public function getCollapseId()
    {
        return "navbar-collapse";
    }

    public function getBrandTitle()
    {
        return "";
    }

    public function getBrandLink()
    {
        return "#";
    }

}
