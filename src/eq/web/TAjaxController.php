<?php


namespace eq\web;


use EQ;

trait TAjaxController
{

    protected function init()
    {
        EQ::app()->bind("beforeRender", function() {
            EQ::app()->client_script->addBundle("eq.ajax");
        });
    }

} 