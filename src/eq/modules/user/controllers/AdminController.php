<?php

namespace eq\modules\user\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\modules\admin\assets\AdminAsset;
use eq\web\Controller;

class AdminController extends Controller
{

    use TModuleClass;

    protected function permissions()
    {
        return [
            'user,guest' => ["deny", "#all"],
        ];
    }

    public function getTemplate()
    {
        return EQ::getAlias("@modules.eq:admin/templates/main.php");
    }

    public function actionIndex()
    {
        $this->render("admin/index");
    }

}