<?php

namespace eq\modules\ajax;

use EQ;
use eq\assets\eq\AjaxAsset;
use eq\base\ModuleBase;
use eq\modules\i18n\I18nModule;

class AjaxModule extends ModuleBase
{

    public function getUrlPrefix()
    {
        return $this->config("url_prefix", "/ajax");
    }

    public function init()
    {
        EQ::app()->bind("modules.eq:i18n.beforeLoadFiles", function (I18nModule $module) {
            $module->addDir($this->location."/locale");
        });
    }

    public function webInit()
    {
        EQ::app()->bind("beforeRender", function() {
            AjaxAsset::register();
        });
    }

} 