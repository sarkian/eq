<?php
/**
 * Last Change: 2014 Apr 10, 12:46
 */

namespace eq\modules\admin;

use EQ;
use eq\helpers\Arr;

class AdminModule extends \eq\base\ModuleBase
{

    use \eq\base\TAutobind;

    protected $config;

    public function __construct($config)
    {
        $this->config = Arr::extend($config, [
            'url' => "/admin",
        ]);
        $this->registerComponent("admin", AdminComponent::className());
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
