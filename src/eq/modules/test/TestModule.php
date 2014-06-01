<?php

namespace eq\modules\test;

use EQ;
use eq\base\ModuleBase;
use eq\base\ModuleException;

class TestModule extends ModuleBase
{

    protected $title = "EQ Test";
    protected $description = [
        'en_US' => "Test module",
        'ru_RU' => "Тестовый модуль",
    ];

    public function getDependencies()
    {
        return [
            "eq:cron",
//            "eq:unknown",
        ];
    }

    protected function init()
    {

    }

}
