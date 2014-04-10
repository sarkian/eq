<?php
/**
 * Last Change: 2014 Apr 05, 14:31
 */

namespace eq\web;

use EQ;

class JSData
{

    public function __construct($config = [])
    {
        EQ::app()->client_script->addBundle("eq.js-data", true);
        EQ::app()->bind("beforeEcho", [$this, "__beforeEcho"]);
    }

    public function __beforeEcho()
    {
        // EQ::app()->client_script->addJs("alert('ok')");
    }

}
