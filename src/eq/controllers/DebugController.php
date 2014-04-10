<?php

namespace eq\controllers;

use EQ;

class DebugController extends \eq\web\Controller
{

    protected $template = "debug";

    public function actionDefault()
    {
        EQ::app()->header("Content-type", "text/html");
        $e = EQ::app()->exception;
        $etype = $e instanceof \eq\base\UncaughtExceptionException
            ? "Uncaught Exception: ".get_class($e->getException())
            : $e->getType();
        $this->page_title = $e->getType();
        $this->render('debug/default', ['e' => $e, 'etype' => $etype]);
    }

}
