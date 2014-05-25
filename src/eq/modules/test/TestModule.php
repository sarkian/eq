<?php

namespace eq\modules\test;

use EQ;
use eq\base\ModuleBase;

class TestModule extends ModuleBase
{

    public function getDepends()
    {
        return [
            "eq:cron",
            "eq:unknown",
        ];
    }

    protected function init()
    {

    }

}
