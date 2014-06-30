<?php

namespace eq\web;

use EQ;
use eq\base\AppBase;
use eq\base\ExceptionBase;
use eq\base\LoaderException;
use eq\base\ModuleBase;
use eq\controllers\DebugController;
use eq\controllers\ErrorsController;
use eq\orm\Model;
use eq\modules\navigation\NavigationComponent;
use eq\modules\user\models\User;
use eq\php\ErrorException;
use eq\base\UncaughtExceptionException;
use eq\base\ComponentException;
use eq\base\InvalidParamException;
use eq\base\Loader;
use eq\web\route\Route;
use Exception;

defined("EQ_ASSETS_DBG") or define("EQ_ASSETS_DBG", EQ_DBG);

/**
 * @property ClientScript client_script
 * @property Jsdata jsdata
 * @property ThemeBase theme
 * @property Route route
 * @property Request request
 * @property Session session
 * @property Header header
 * @property Cookie cookie
 * @property IIdentity|Model|User user
 * @property array route_files
 * @property string controller_name
 * @property string action_name
 * @method void header()
 * @method string|null cookie(string $name, string $value = null, array $options = [])
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
        return EQ_RECOVERY ? [] : [
            'modules.*' => "all",
            'app.*' => "all",
            'site.*' => "all",
            'var.*' => "all",
        ];
    }

    public function __construct($config)
    {
        parent::$_app = $this;
        self::setAlias("@www",
            realpath(self::getAlias($this->config("web.content_root"))));
        self::setAlias("@web", "");
        parent::__construct($config);
        $this->bind("beforeRender", [$this, "__beforeRender"]);
    }

    public function __beforeRender()
    {
        $this->unbind("beforeRender", [$this, "__beforeRender"]);
        $this->getTheme()->registerAssets();
        foreach($this->config("web.preload_assets", []) as $asset)
            $this->client_script->addBundle($asset, EQ_DBG);
    }

    /**
     * @return ThemeBase
     * @throws \eq\base\InvalidParamException
     */
    public function getTheme()
    {
        if(!$this->theme)
            $this->setTheme($this->config("site.theme", "bootstrap"));
        return $this->theme;
    }

    public function setTheme($theme)
    {
        if($this->theme)
            self::warn("Theme changed");
        $cname = Loader::autofindClass($theme, "themes\\$theme", "Theme");
        if(!$cname)
            throw new InvalidParamException("Theme class not found: $theme");
        $this->theme = new $cname();
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

    public function createAbsoluteUrl($path, $vars = [], $get_vars = [])
    {
        return $this->request->root.$this->createUrl($path, $vars, $get_vars);
    }

    public function redirect($url, $status = 302, $message = "Found")
    {
        throw new HttpRedirectException($url, $status, $message);
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
                    $controller->useActionResult($result);
                $out = ob_get_clean();
                $this->trigger("beforeEcho");
                echo $out;
            }
            else {
                throw new HttpException(404, "Page not found: ".$this->request->uri);
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
        catch(Exception $e_unc) {
            $this->processUncaughtException($e_unc);
        }
        $this->trigger("shutdown");
    }

    public function processFatalError(array $err)
    {
        $this->clearOutBuff();
        $this->processException(
            new ErrorException($err['type'], $err['message'], 
                $err['file'], $err['line'], [])
        );
    }

    public function processException(ExceptionBase $e)
    {
        $this->trigger("exception", $e);
        if(defined('EQ_DBG') && EQ_DBG) {
            $cname = $this->app_namespace.'\controllers\DebugController';
            try {
                $controller = new $cname();
            }
            catch(LoaderException $e_load) {
                $controller = new DebugController();
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

    public function processUncaughtException(Exception $e)
    {
        $this->clearOutBuff();
        $this->processException(
            new UncaughtExceptionException($e)
        );
    }

    protected function loadModule($name, $cname)
    {
        if(isset($this->modules_by_name[$name]))
            return $this->modules_by_name[$name];
        $module = parent::loadModule($name, $cname);
        $fname = $module->location."/route.eqrt";
        if(file_exists($fname))
            $this->route->addFile($fname, $module->url_prefix, "modules.".$module->name);
        return $module;
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
                'class' => 'eq\web\Jsdata',
                'preload' => true,
            ],
        ]);
    }

    protected function defaultComponents()
    {
        $user_class = $this->app_namespace.'\models\User';
        if(!Loader::classExists($user_class))
            $user_class = 'eq\models\User';
        if(!isset(class_implements($user_class)['eq\web\IIdentity']))
            throw new ComponentException('User class must be implements eq\web\IIdentity');
        return array_merge(parent::defaultComponents(), [
            'user' => [
                'class' => $user_class,
            ],
        ]);
    }

    protected function processHttpException(HttpException $e)
    {
        EQ::app()->header->status($e->getStatus(), EQ_DBG ? $e->getMessage() : null);
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
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach(ob_list_handlers() as $handler)
            ob_end_clean();
    }

}
