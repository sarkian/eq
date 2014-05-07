<?php

namespace eq\controllers;

use EQ;
use eq\base\UncaughtExceptionException;
use eq\web\Controller;

class DebugController extends Controller
{

    protected $template = "debug";

    public function actionDefault()
    {
        EQ::app()->header("Content-type", "text/html");
        $e = EQ::app()->exception;
        $etype = $e instanceof UncaughtExceptionException
            ? "Uncaught Exception: ".get_class($e->getException())
            : $e->getType();
        $this->page_title = $e->getType();
        $this->render('debug/default', ['e' => $e, 'etype' => $etype]);
    }

}
