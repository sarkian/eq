<?php

namespace eq\web;

use EQ;
use eq\base\AppBase;
use eq\base\ExceptionBase;
use eq\base\LoaderException;
use eq\controllers\ErrorsController;
use eq\data\Model;
use eq\modules\user\models\Users;
use eq\php\ErrorException;
use eq\base\UncaughtExceptionException;
use eq\base\ComponentException;
use eq\base\InvalidParamException;
use eq\base\Loader;
use eq\web\route\Route;

defined("EQ_ASSETS_DBG") or define("EQ_ASSETS_DBG", EQ_DBG);

/**
 * @property ClientScript client_script
 * @property ThemeBase theme
 * @property Route route
 * @property Request request
 * @property Session session
 * @property Header header
 * @property IIdentity|Model|Users user
 * @property array route_files
 * @property string controller_name
 * @property string action_name
 * @method void header()
 */
class WebApp extends AppBase
{

    protected $controller_name;
    protected $action_name;
    protected $http_exception;
    protected $theme;

    public static function widget($name)
    {
        $args = func_get_args();
        array_shift($args);
        $cname = EQ::app()->getTheme()->widgetClass($name);
        $cname or $cname = Loader::autofindClass($name, "widgets", "");
        if(!$cname)
            throw new InvalidParamException("Widget class not found: $name");
        $reflect = new \ReflectionClass($cname);
        return $reflect->newInstanceArgs($args);
    }

    protected static function defaultStaticMethods()
    {
        return array_merge(parent::defaultStaticMethods(), [
            'widget' => ['eq\web\WebApp', "widget"],
        ]);
    }

    protected function configPermissions()
    {
        return [
            'site.*' => "all",
        ];
    }

    public function __construct($config)
    {
        parent::$_app = $this;
        parent::__construct($config);
        self::setAlias("@www", 
            realpath(self::getAlias($this->config("web.content_root"))));
        self::setAlias("@web", "");
        $this->bind("beforeRender", [$this, "__beforeRender"]);
        foreach($this->config("web.preload_assets", []) as $asset)
            $this->client_script->addBundle($asset, EQ_DBG);
    }

    public function __beforeRender()
    {
        $this->unbind("beforeRender", [$this, "__beforeRender"]);
        $this->getTheme()->registerAssets();
        // foreach($this->config("web.preload_assets", []) as $asset)
            // $this->client_script->addBundle($asset, EQ_DBG);
    }

    /**
     * @return ThemeBase
     * @throws \eq\base\InvalidParamException
     */
    public function getTheme()
    {
        if(!$this->theme) {
            $tname = $this->config("web.theme", "bootstrap");
            $cname = Loader::autofindClass($tname, "themes\\$tname", "Theme");
            if(!$cname)
                throw new InvalidParamException("Theme class not found: $tname");
            $this->theme = new $cname();
        }
        return $this->theme;
    }

    public function getControllerName()
    {
        return $this->controller_name;
    }

    public function getActionName()
    {
        return $this->action_name;
    }

    public function getRouteFiles()
    {
        $conf = $this->config("web.route", []);
        $files = array_combine($conf, array_fill(0, count($conf), ["", ""]));
        foreach($this->modules_by_name as $name => $module) {
            $fname = $module->location."/route.eqrt";
            if(file_exists($fname))
                $files[$fname] = [$module->url_prefix, "modules.$name"];
        }
        return $files;
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

    public function createAbsoluteUrl($path, $vars = [], $get_vars = [])
    {
        return $this->request->root.$this->createUrl($path, $vars, $get_vars);
    }

    public function redirect($url, $status = 302, $message = "")
    {
        throw new HttpRedirectException($url, $status, $message = "Found");
    }

    public function run()
    {
        $this->trigger("request");
        try {
            $this->route->processRequest();
            if($this->route->found) {
                $this->controller_name = $this->route->controller_name;
                $this->action_name = $this->route->action_name;
                $cname = $this->route->controller_class;
                $method = $this->route->action_method;
                $controller = new $cname();
                $action = new ReflectionAction($controller, $method);
                ob_start();
                $result = $action->call($this->route->vars);
                if(!is_null($result))
                    $controller->useActionResult();
                $out = ob_get_clean();
                $this->trigger("beforeEcho");
                echo $out;
            }
            else {
                throw new HttpException(404);
            }
        }
        catch(HttpRedirectException $e_redir) {
            $this->clearOutBuff();
            $this->header->status($e_redir->getStatus(), $e_redir->getMessage());
            $this->header("Location", $e_redir->getUrl());
            $this->trigger("beforeEcho");
            exit;
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
                'class' => 'eq\web\route\Route',
            ],
            'session' => [
                'class' => 'eq\web\Session',
                'preload' => true,
            ],
            'client_script' => [
                'class' => 'eq\web\ClientScript',
            ],
            'jsdata' => [
                'class' => 'eq\web\JSData',
            ],
        ]);
    }

    protected function defaultComponents()
    {
        $user_class = $this->app_namespace.'\models\Users';
        if(!Loader::classExists($user_class))
            $user_class = 'eq\models\Users';
        if(!isset(class_implements($user_class)['eq\web\IIdentity']))
            throw new ComponentException('User class must be implements eq\web\IIdentity');
        return [
            'user' => [
                'class' => $user_class,
                'config' => null,
            ],
        ];
    }

    protected function processHttpException(HttpException $e)
    {
        EQ::app()->header("Content-type", "text/html");
        $cname = $this->app_namespace.'\controllers\ErrorsController';
        try {
            $controller = new $cname();
        }
        catch(LoaderException $e_loader) {
            $controller = new ErrorsController();
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
