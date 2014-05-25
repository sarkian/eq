<?php

namespace eq\modules\ajax\controllers;

use eq\modules\ajax\AjaxErrorException;
use eq\modules\ajax\AjaxException;
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
            if($route->found) {
                $cname = $route->controller_class;
                $method = $route->action_method;
                $controller = new $cname();
                if(!$controller instanceof Controller)
                    throw new ControllerException(
                        'Controller class must be a subclass of eq\web\Controller: '.$cname);
                $action = new ReflectionAction($controller, $method);
                $tag = $action->docblock->tag("ajax");
                if(!$tag->exists())
                    throw new HttpException(404);
                try {
                    $val = $tag->wfirst();
                    $success = true;
                    $error = "";
                    try {
                        $res = $action->call($route->vars);
                    }
                    catch(AjaxErrorException $e) {
                        $success = false;
                        $error = $e->getMessage();
                        $res = null;
                    }
                    catch(\Exception $e) {
                        $success = false;
                        $error = EQ_DBG ? $e->getMessage() : EQ::t("Application error");
                        $res = null;
                    }
                    switch($val) {
                        case null:
                            EQ::app()->header("Content-type", "application/json");
                            echo json_encode([
                                'success' => $success,
                                'error' => $error,
                                'data' => $res,
                            ]);
                            break;
                        case "json":
                            EQ::app()->header("Content-type", "application/json");
                            echo json_encode($res);
                            break;
                        case "raw":
                            echo $res;
                            break;
                        default:
                            throw new AjaxException("Invalid @ajax tag value: $val");
                    }
                }
                catch(RouteException $e) {
                    throw new HttpException(400);
                }
            }
            else {
                throw new HttpException(404);
            }
        }
        catch(RouteException $e) {
            throw new HttpException(404);
        }
    }

} 