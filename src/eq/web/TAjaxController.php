<?php


namespace eq\web;


use EQ;

/**
 * @property string|null template
 */
trait TAjaxController
{

    protected function init()
    {
        EQ::app()->bind("beforeRender", function() {
            if(EQ::app()->request->request("ajax") !== null) {
                $this->template = null;
            }
            else {
                EQ::app()->client_script->addBundle("eq.ajax");
            }
        });
    }

} 