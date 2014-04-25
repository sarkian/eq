<?php
/**
 * Last Change: 2014 Apr 25, 19:55
 */

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\ModuleBase;
use eq\base\ModuleException;

class AdminController extends \eq\web\Controller
{

    public function actionIndex()
    {
        
    }

    public function actionTest()
    {
        EQ::app()->header("Content-type", "text/plain");

        // EQ::app()->module("eq/i18n")->test();
        // EQ::app()->test();
        print_r(EQ::app()->available_modules);
    }

}
