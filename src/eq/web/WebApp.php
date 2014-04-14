<?php

namespace eq\web;

use EQ;
use eq\base\ExceptionBase;
use eq\base\LoaderException;
use eq\php\ErrorException;
use eq\base\UncaughtExceptionException;
use eq\base\AppException;
use eq\base\InvalidConfigException;
use eq\base\ComponentException;
use eq\base\Loader;

defined("EQ_ASSETS_DBG") or define("EQ_ASSETS_DBG", false);

class WebApp extends \eq\base\AppBase
{

    protected $controller_name;
    protected $action_name;
    protected $http_exception;

    // protected $route;
    // protected $client_script;

    public function __construct($config)
    {
        parent::$_app = $this;
        parent::__construct($config);
        self::setAlias("@www", 
            realpath(self::getAlias($this->config("web.content_root"))));
        self::setAlias("@web", "");
        // $this->bind("beforeRender", [$this, "__beforeRender"]);
        foreach($this->config("web.preload_assets", []) as $asset)
            $this->client_script->addBundle($asset, EQ_DBG);
    }

    public function __beforeRender()
    {
        $this->unbind("beforeRender", [$this, "__beforeRender"]);
        // foreach($this->config("web.preload_assets", []) as $asset)
            // $this->client_script->addBundle($asset, EQ_DBG);
    }

    public function getContentRoot()
    {
        return $this->content_root;
    }

    public function getControllerName()
    {
        return $this->controller_name;
    }

    public function getActionName()
    {
        return $this->action_name;
    }

    public function getHttpException()
    {
        return $this->http_exception;
    }

    public function setHttpException(HttpException $e)
    {
        $this->http_exception = $e;
    }

    public function createUrl($path, $vars = [], $get_vars = [])
    {
        return $this->route->createUrl($path, $vars, $get_vars);
    }

    public function run()
    {
        $this->trigger("request");
        try {
            $this->route->processRequest();
            if($this->route->found) {
                $this->controller_name = $this->route->controller_name;
                $this->action_name = $this->route->action_name;
                if($this->route->controller_inst) {
                    $controller = $this->route->controller_inst;
                }
                else {
                    $cname = $this->route->controller_class
                        ? $this->route->controller_class
                        : Loader::autofindClass($this->route->controller, "controllers", "");
                    if(!$cname) {
                        if($this->route->dynamic_controller)
                            throw new HttpException(404, "Page not found");
                        else
                            throw new ControllerException(
                                "Controller not found: {$this->controller_name}");
                    }
                    $controller = new $cname();
                }
                $actname = $this->route->action;
                if(!method_exists($controller, $actname))
                    if($this->route->dynamic_action)
                        throw new HttpException(404, "Page not found");
                    else
                        throw new ControllerException(
                            "Action not found: {$this->controller_name}."
                            .$this->action_name);
                ob_start();
                $action = new ReflectionAction($controller, $actname);
                $result = $action->call($this->route->vars);
                // $result = $controller->{$actname}();
                if(!is_null($result))
                    $controller->useActionResult($result);
                $out = ob_get_clean();
                $this->trigger("beforeEcho");
                echo $out;
            }
            else {
                throw new HttpException(404, "Page not found");
            }
        }
        catch(HttpException $e_http) {
            $this->clearOutBuff();
            $this->processHttpException($e_http);
        }
        catch(ExceptionBase $e_base) {
            // $this->unbind("exception", [$this, "__onException"]);
            // $this->unbind("exception");
            $this->clearOutBuff();
            $this->processException($e_base);
        }
        catch(\Exception $e_unc) {
            $this->processUncaughtException($e_unc);
        }
    }

    public function processFatalError($err)
    {
        $this->clearOutBuff();
        $this->processException(
            new ErrorException($err['type'], $err['message'], 
                $err['file'], $err['line'], [])
        );
    }

    public function processException($e)
    {
        if(defined('EQ_DBG') && EQ_DBG) {
            $cname = $this->app_namespace.'\\controllers\\DebugController';
            try {
                $controller = new $cname();
            }
            catch(LoaderException $e_load) {
                $controller = new \eq\controllers\DebugController();
            }
            $actname = 'action'.$e->getType();
            $this->exception = $e;
            // ob_start();
            if(method_exists($controller, $actname))
                $controller->{$actname}();
            else
                $controller->actionDefault();
            // $out = ob_get_clean();
            // $this->trigger("beforeEcho");
            // echo $out;
        }
        else
            $this->processHttpException(
                new HttpException(500, $e->getMessage()));
    }

    public function processUncaughtException($e)
    {
        $this->clearOutBuff();
        $this->processException(
            new UncaughtExceptionException($e)
        );
    }

    protected function systemComponents()
    {
        // TODO Move "user" component into module
        $user_class = $this->app_namespace."\models\Users";
        if(!Loader::classExists($user_class))
            $user_class = "eq\models\Users";
        if(!isset(class_implements($user_class)["eq\web\IIdentity"]))
            throw new ComponentException(
                "User class must be implements eq\web\IIdentity");
        return array_merge(parent::systemComponents(), [
            'request' => [
                'class' => 'eq\web\Request',
                'preload' => true,
            ],
            'header' => [
                'class' => 'eq\web\Header',
                'preload' => true,
            ],
            'cookie' => [
                'class' => 'eq\web\Cookie',
                'preload' => true,
            ],
            'route' => [
                'class' => 'eq\web\Route',
                'config' => $this->config("web.route", []),
            ],
            'client_script' => [
                'class' => 'eq\web\ClientScript',
            ],
            'jsdata' => [
                'class' => 'eq\web\JSData',
            ],
            'user' => [
                'class' => $user_class,
                'config' => null,
            ],
        ]);
    }

    protected function dummyComponents()
    {
        return [
            'user' => [
                'class' => "eq\models\Users",
                'config' => null,
            ],
        ];
    }

    protected function processHttpException($e)
    {
        EQ::app()->header("Content-type", "text/html");
        $cname = $this->app_namespace."\controllers\ErrorsController";
        try {
            $controller = new $cname();
        }
        catch(LoaderException $e_loader) {
            $controller = new \eq\controllers\ErrorsController();
        }
        $actname = 'action'.$e->getStatus();
        if(method_exists($controller, $actname))
            $controller->{$actname}();
        else
            $controller->actionDefault();
    }

    protected function clearOutBuff()
    {
        foreach(ob_list_handlers() as $handler)
            ob_end_clean();
    }

}
