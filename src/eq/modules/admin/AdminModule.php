<?php
/**
 * Last Change: 2014 Apr 25, 19:59
 */

namespace eq\modules\admin;

use EQ;
use eq\helpers\Arr;

class AdminModule extends \eq\base\ModuleBase
{

    use \eq\base\TAutobind;

    protected $config;

    protected function init()
    {
        $this->autobind();
    }

    public function __onI18n_beforeLoadFiles()
    {
        EQ::app()->module("eq/i18n")->addDir(__DIR__."/locale", "admin");
    }

}
