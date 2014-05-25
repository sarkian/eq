<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\modules\admin\assets\AdminAsset;
use eq\web\Controller;

class ModulesController extends Controller
{

    use TModuleClass;

    protected $template = "main";

    protected function permissions()
    {
        return [
            'user,guest' => ["deny", "#all"],
        ];
    }

    public function actionIndex()
    {
        AdminAsset::addJs("modules");
        $modules = EQ::app()->available_modules;
        $this->render("modules/index", ['modules' => $modules]);
    }

}
