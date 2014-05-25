<?php

namespace eq\controllers;

use EQ;
use eq\modules\clog\Clog;
use eq\web\Controller;

class ErrorsController extends Controller
{

    protected $template = "error";

    public function actionDefault()
    {
        $e = EQ::app()->http_exception;
        $messages = $this->messages();
        $status = $e->getStatus();
        if((EQ_DBG || $status < 500) && $e->getMessage())
            $message = $e->getMessage();
        elseif(isset($messages[$status]))
            $message = EQ::t($messages[$status]);
        else
            $message = "Unknown Error";
        if($this->findViewFile("errors/$status"))
            $view = "errors/$status";
        elseif($this->findViewFile("errors/default"))
            $view = "errors/default";
        else {
            echo '<div style="text-align: center;">'.
                '<h2>'.$status.'</h2>'.$message.'</div>';
            return;
        }
        $this->createTitle("$status $message");
        $this->render($view, ['status' => $status, 'message' => $message]);
    }

    protected function messages()
    {
        return [
            400 => "Bad Request",
            404 => "Page not found",
            500 => "Internal Server Error",
        ];
    }

}
