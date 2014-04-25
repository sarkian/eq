<?php
/**
 * Last Change: 2014 Apr 25, 18:35
 */

namespace eq\modules\admin\controllers;

class ModulesController extends \eq\web\Controller
{

    use \eq\base\TModuleClass;

    protected $template = "main";

    public function actionIndex()
    {
        $this->render("modules/index");
    }

}
