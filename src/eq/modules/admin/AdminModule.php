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

    protected static $removed_modules = [];

    protected $title = "EQ Admin";
    protected $description = [
        'ru_RU' => "Администрирование сайта",
    ];

    protected $nav_items = [];

    protected static function preInit()
    {
        EQ::app()->bind("moduleNotFound", function($mname) {
            self::$removed_modules[] = $mname;
        });
    }
    
    public function configDefaults()
    {
        return [];
    }

    public function init()
    {
        EQ::app()->bind("modules.eq:i18n.beforeLoadFiles", function (I18nModule $module) {
            $module->addDir($this->location."/locale", "admin");
        });
    }

    public function webInit()
    {
        $this->nav_items = [
            'home' => [
                'link' => "/",
                'icon' => "glyphicon glyphicon-home",
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
            foreach(array_reverse($this->nav_items) as $item)
                $module->prependItem("admin", $item);
        });
        EQ::app()->bind("beforeRender", function() {
            if(!EQ::app()->user->isAdmin() || !$this->isAdminUrl())
                return;
            // TODO: Make it configurable
            EQ::app()->setTheme("bootstrap_darkly");
            AdminAsset::register();
            foreach(self::$removed_modules as $mname) {
                $message = EQ::k("admin.removedModule", $mname);
                EQ::app()->client_script->notify($message, "notice");
                EQ::app()->dbconfig->remove("modules.$mname");
            }
        });
    }

    public function getDependencies()
    {
        return [
            "eq:navigation",
            "eq:dbconfig",
            "eq:ajax",
        ];
    }

    public function addPage($name, $item)
    {
        if(isset($this->nav_items[$name]))
            $this->nav_items[$name]['items'][] = $item;
        else
            $this->nav_items[$name] = $item;
    }

    protected function isAdminUrl()
    {
        return !strncmp(EQ::app()->request->uri, "/admin", 6);
    }

}
