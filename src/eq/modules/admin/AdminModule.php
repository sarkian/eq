<?php

namespace eq\modules\admin;

use EQ;
use eq\base\ModuleBase;
use eq\base\TAutobind;
use eq\modules\admin\assets\AdminAsset;
use eq\modules\i18n\I18nModule;
use eq\modules\navigation\NavigationModule;

class AdminModule extends ModuleBase
{

    use TAutobind;

    protected $title = "EQ Admin";
    protected $description = [
        'ru_RU' => "Администрирование сайта",
    ];

    protected $nav_items = [];

    public function webInit()
    {
        EQ::app()->bind("modules.eq:i18n.beforeLoadFiles", function(I18nModule $module) {
            $module->addDir($this->location."/locale", "admin");
        });
        $this->nav_items = [
            'home' => [
                'link' => "/",
                'icon' => "home",
                'tooltip' => EQ::t("Back to site"),
            ],
            'modules' => [
                'title' => EQ::t("Modules"),
                'items' => [
                    [
                        'route' => $this->route("modules.index"),
                        'title' => EQ::t("Manage"),
                    ],
                    "#divider",
                ],
            ],
        ];
        EQ::app()->bind("modules.eq:navigation.navRender.admin",
        function(NavigationModule $module) {
            foreach($this->nav_items as $item)
                $module->addItem("admin", $item);
        });
    }

    public function getDepends()
    {
        return [
            "eq:navigation",
            "eq:dbconfig",
        ];
    }

    public function addPage($name, $item)
    {
        if(isset($this->nav_items[$name]))
            $this->nav_items[$name]['items'][] = $item;
        else
            $this->nav_items[$name] = $item;
    }

}
