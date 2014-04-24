<?php

namespace eq\web;

use EQ;
use eq\cgen\ViewRenderer;
use eq\helpers\Str;

abstract class Controller
{

    use \eq\base\TObject;

    protected $page_title = '';
    protected $head_content = '{{$HEAD_CONTENT}}';
    protected $body_begin_content = '{{$BODY_BEGIN_CONTENT}}';
    protected $body_end_content = '{{$BODY_END_CONTENT}}';

    protected static $__view_vars__ = null;

    public final function __construct()
    {
        $this->init();
        $this->processPermissions();
    }

    public static function className()
    {
        return get_called_class();
    }

    public static function routes($line)
    {
        $cname = preg_replace("/^.+\\\controllers\\\/", "", get_called_class());
        $cname = str_replace('\\', ".", $cname);
        $cname = Str::method2cmd(preg_replace("/Controller$/", "", $cname));
        return str_replace('{{'.$cname.'}}', "/{action<[a-z\-]+>}", $line)
            ." $cname.{action}";
    }

    public function getTemplate()
    {
        return null;
    }

    public function actionDefault() {}

    public function useActionResult($result) {}

    public function reflectAction($name)
    {
        return new ReflectionAction($this, $name);
    }

    protected function permissions()
    {
        return [
            'guest' => ["allow", "all"],
            'user' => ['allow', "all"],
            'admin' => ["allow", "all"],
        ];
    }

    protected function init() {}

    protected function processPermissions()
    {
        $perms = $this->permissions();
        $action = EQ::app()->action_name;
        $default = ["allow", "all"];
        if(isset(EQ::app()->user) && EQ::app()->user->isAuth()) {
            if(EQ::app()->user->isAdmin())
                $perms = isset($perms['admin']) ? $perms['admin'] : $default;
            else $perms = isset($perms['user']) ? $perms['user'] : $default;
        }
        else
            $perms = isset($perms['guest']) ? $perms['guest'] : $default;
        if(!isset($perms[0], $perms[1]))
            throw new ControllerException("Invalid permissions");
        $callback = isset($perms[2]) ? $perms[2] : 404;
        if(!is_callable($callback)) {
            $status = $callback;
            $callback = function() use($status) {
                throw new HttpException($status);
            };
        }
        if($perms[0] === "allow") {
            if($perms[1] === "all")
                return;
            if(!in_array(EQ::app()->action_name, explode(',', $perms[1])))
                $callback();
            //exit;
        }
        if($perms[0] === "deny") {
            if($perms[1] === "all")
                $callback();
            elseif(in_array(EQ::app()->action_name, explode(",", $perms[1])))
                $callback();
            //exit;
        }
    }

    protected function redir($url, $status = null)
    {
        EQ::app()->redirect($url, $status);
    }

    protected function setTitle($title)
    {
        EQ::app()->client_script->setTitle($title);
    }

    protected function createTitle($title = "...")
    {
        EQ::app()->client_script->createTitle($title);
    }

    protected function render($view, $view_vars = [])
    {
        EQ::app()->trigger("beforeRender");
        self::$__view_vars__ = $view_vars;
        $view_file = $this->findViewFile($view);
        if(!$view_file)
            throw new ControllerException("View file not found: $view");
        ob_start();
        $content = ViewRenderer::renderFile($view_file, $view_vars);
        $this->renderingEnd($content);
        $out = ob_get_clean();
        EQ::app()->trigger("beforeEcho");
        if($this->template) {
            $out = preg_replace('/\{\{\$HEAD_CONTENT\}\}/',
                EQ::app()->client_script->renderHead(), $out, 1);
            $out = preg_replace('/\{\{\$BODY_BEGIN_CONTENT\}\}/',
                EQ::app()->client_script->renderBegin(), $out, 1);
            $out = preg_replace('/\{\{\$BODY_END_CONTENT\}\}/',
                EQ::app()->client_script->renderEnd(), $out, 1);
        }
        echo $out;
        exit;
    }

    protected function findViewFile($view_file)
    {
        if(strpos($view_file, '/') === false)
            $view_file = EQ::app()->controller_name.'/'.$view_file;
        if(file_exists($view_file))
            return $view_file;
        $view_file_path = APPROOT."/views/$view_file.php";
        file_exists($view_file_path) or $view_file_path = EQROOT."/views/$view_file.php";
        return file_exists($view_file_path) ? $view_file_path : false;
    }

    protected function findTemplate()
    {
        if(!$this->template)
            return false;
        $fname = APPROOT."/templates/{$this->template}.php";
        file_exists($fname) or $fname = EQROOT."/templates/{$this->template}.php";
        if(file_exists($fname))
            return $fname;
        else
            throw new ControllerException("Template not found: {$this->template}");
    }

    /*
     * End rendering
     *
     * @property string $content
     * @throws ControllerException
     */
    private function renderingEnd($content)
    {
        $tpl = $this->findTemplate();
        if($tpl)
            require $tpl;
        else
            echo $content;
    }

}

