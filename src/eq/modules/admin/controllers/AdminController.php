<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\Cache;
use eq\base\InvalidCallException;
use eq\base\LoaderException;
use eq\base\ModuleBase;
use eq\base\ModuleException;
use eq\base\TModuleClass;
use eq\base\UncaughtExceptionException;
use eq\helpers\Arr;
use eq\modules\admin\assets\AdminAsset;
use eq\php\ErrorException;
use eq\php\NoticeException;
use eq\web\Controller;

class AdminController extends AdminControllerBase
{

    use TModuleClass;

    public function actionIndex()
    {
        $this->render("index");
    }

    public function actionTest()
    {
        EQ::app()->header("Content-type", "text/plain");

        print_r(EQ::app()->available_modules);

//        try {
//            var_dump(class_exists("nonexistent"));
//        }
//        catch(LoaderException $e) {}
//        $e = new LoaderException("test");

//        $this->render("index");
    }

}
