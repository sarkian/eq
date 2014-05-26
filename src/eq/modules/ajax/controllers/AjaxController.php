<?php

namespace eq\modules\ajax\controllers;

use eq\cgen\reflection\ReflectionClass;
use eq\modules\ajax\AjaxErrorException;
use eq\modules\ajax\AjaxException;
use eq\modules\ajax\AjaxReflectionAction;
use eq\modules\ajax\AjaxResponse;
use eq\web\Controller;
use EQ;
use eq\web\ControllerException;
use eq\web\HttpException;
use eq\web\ReflectionAction;
use eq\web\route\RouteException;

class AjaxController extends Controller
{

    public function actionHandle($path)
    {
        try {
            $route = EQ::app()->route;
            $route->redirect($path);
            if(!$route->found)
                throw new HttpException(404);
            $action = new AjaxReflectionAction($route->controller_class, $route->action_method);
            $action->call($route->vars);
        }
        catch(RouteException $e) {
            throw new HttpException(404);
        }
    }

}