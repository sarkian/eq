<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\ModuleBase;
use eq\base\ModuleException;
use eq\base\TModuleClass;
use eq\web\Controller;

class AdminController extends Controller
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
        $this->render("index");
    }

    public function actionTest()
    {
        EQ::app()->header("Content-type", "text/plain");

        // EQ::app()->module("eq/i18n")->test();
        // EQ::app()->test();
        print_r(EQ::app()->available_modules);
    }

}
