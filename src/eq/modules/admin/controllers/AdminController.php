<?php
/**
 * Last Change: 2014 Apr 24, 21:33
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

        // echo EQ::app()->module("admin")->fullname;

        print_r(EQ::app()->modules_by_fullname);
        // print_r(EQ::app()->available_modules);
    }

}
