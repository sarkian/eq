<?php

namespace eq\modules\admin;

use EQ;
use eq\base\ModuleBase;
use eq\base\TAutobind;

class AdminModule extends ModuleBase
{

    use TAutobind;

    protected $config;

    protected function init()
    {
        $this->autobind();
    }

    public function __onI18n_beforeLoadFiles()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        EQ::app()->module("eq:i18n")->addDir(__DIR__."/locale", "admin");
    }

}
