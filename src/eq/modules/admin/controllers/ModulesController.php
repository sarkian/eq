<?php
/**
 * Last Change: 2014 Apr 24, 20:54
 */

namespace eq\modules\admin\controllers;

class ModulesController extends \eq\web\ModuleController
{

    protected $template = "main";

    public function actionIndex()
    {
        $this->render("modules/index");
    }

}
