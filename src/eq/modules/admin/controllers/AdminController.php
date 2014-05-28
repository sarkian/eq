<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\InvalidCallException;
use eq\base\LoaderException;
use eq\base\ModuleBase;
use eq\base\ModuleException;
use eq\base\TModuleClass;
use eq\base\UncaughtExceptionException;
use eq\modules\admin\assets\AdminAsset;
use eq\php\ErrorException;
use eq\php\NoticeException;
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

    protected function beforeRender()
    {
        AdminAsset::register();
    }

    public function actionIndex()
    {
        $this->render("index");
    }

    public function actionTest()
    {
        EQ::app()->header("Content-type", "text/plain");

        echo EQ::app()->createUrl("modules.eq:clog.clog.process",
            ['key' => "KEY"], ["EQ_RECOVERY", 'some' => "ok"]);

//        try {
//            var_dump(class_exists("nonexistent"));
//        }
//        catch(LoaderException $e) {}
//        $e = new LoaderException("test");

//        $this->render("index");
    }

}
