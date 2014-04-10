<?php
/**
 * Last Change: 2014 Apr 08, 23:23
 */

namespace eq\modules\test;

use EQ;

class TestModule extends \eq\base\ModuleBase
{

    public function __construct()
    {
        EQ::app()->route->register("GET", "/some/{action}", TestController::className());
    }

}
