<?php
/**
 * Last Change: 2014 Apr 24, 04:11
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
        // EQ::app()->route->register("*", 
            // "/admin/{action}", controllers\AdminController::className());
        // EQ::app()->bind("i18n.beforeLoadFiles", 
            // [$this, "__onI18n_beforeLoadFiles"]);
    }

    public function __onI18n_beforeLoadFiles()
    {
        EQ::app()->i18n->addDir(__DIR__."/locale", "admin");
    }

}
