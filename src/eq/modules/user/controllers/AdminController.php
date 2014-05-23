<?php

namespace eq\modules\user\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\web\Controller;

class AdminController extends Controller
{

    use TModuleClass;

    public function getTemplate()
    {
        return EQ::getAlias("@modules.eq:admin/templates/main.php");
    }

    public function actionIndex()
    {
        $this->render("admin/index");
    }

} 