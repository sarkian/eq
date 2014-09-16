<?php

namespace eq\modules\ajax;

use EQ;
use eq\assets\eq\AjaxAsset;
use eq\base\ModuleBase;
use eq\modules\i18n\I18nModule;
use eq\web\Jsdata;

class AjaxModule extends ModuleBase
{

    public function configDefaults()
    {
        return [
            'url_prefix' => "/ajax",
        ];
    }

    public function getUrlPrefix()
    {
        return $this->config("url_prefix");
    }

    public function init()
    {
        EQ::app()->bind("modules.eq:i18n.beforeLoadFiles", function(I18nModule $module) {
            $module->addDir($this->location."/locale");
        });
    }

    public function webInit()
    {
        EQ::app()->bind("beforeRender", function() {
            AjaxAsset::register();
        });
        EQ::app()->bind("jsdata.register", function(Jsdata $jsdata) {
            $jsdata->set("ajax.url_prefix", $this->config("url_prefix"));
            $jsdata->set("ajax.token", EQ::app()->token);
        });
    }

} 