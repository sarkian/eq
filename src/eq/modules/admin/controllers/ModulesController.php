<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\modules\admin\assets\AdminAsset;
use eq\web\Controller;
use eq\web\TAjaxController;

class ModulesController extends Controller
{

    use TModuleClass;
    use TAjaxController;

    protected $template = "main";

    protected function permissions()
    {
        return [
            'user,guest' => ["deny", "#all"],
        ];
    }

    protected function beforeRender()
    {
        AdminAsset::register();
    }

    public function actionIndex()
    {
        if(EQ::app()->request->filterGet("ajax", "bool", false))
            $this->template = null;
        AdminAsset::addJs("modules");
        $modules = EQ::app()->available_modules;
        $this->render("modules/index", ['modules' => $modules]);
    }

}
