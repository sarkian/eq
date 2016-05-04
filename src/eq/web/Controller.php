<?php

namespace eq\web;

use EQ;
use eq\base\TObject;
use eq\cgen\ViewRenderer;
use eq\helpers\Str;

/**
 * @property string|null template
 */
abstract class Controller
{

    use TObject;

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

    public static function cls()
    {
        return get_called_class();
    }

    public static function routes($line)
    {
        $cname = preg_replace('/^.+\\\controllers\\\/', "", get_called_class());
        $cname = str_replace('\\', ".", $cname);
        $cname = Str::method2cmd(preg_replace("/Controller$/", "", $cname));
        return str_replace('{{'.$cname.'}}', '/{action<[a-z\-]+>}', $line)
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
        return array(
            'guest,user,admin' => ["allow", "#all"],
        );
    }

    protected function beforeRender()
    {

    }

    protected function beforeEcho()
    {

    }

    protected function init() {}

    protected function processPermissions()
    {
        $ustatus = EQ::app()->user->getStatus();
        $trimf = function(&$str) {
            $str = strtolower(trim($str, " \n\r\t"));
        };
        $permissions = $this->permissions();
        if(!is_array($permissions))
            throw new ControllerException("Invalid permissions in ".get_called_class());
        foreach($permissions as $status => $perms) {
            $status = explode(",", $status);
            array_walk($status, $trimf);
            if(!in_array($ustatus, $status))
                continue;
            if(!is_array($perms))
                throw new ControllerException("Invalid permissions in ".get_called_class());
            $perms = array_merge($perms);
            if(count($perms) < 2 || count($perms) > 3)
                throw new ControllerException("Invalid permissions");
            $perm = strtolower(trim($perms[0], " \r\n\t"));
            $actions = strtolower(trim($perms[1], " \r\n\t"));
            $callback = isset($perms[2]) ? $perms[2] : 404;
            if(!is_callable($callback)) {
                if(!is_int($callback) && !is_string($callback))
                    throw new ControllerException("Invalid permissions in ".get_called_class());
                $status = $callback;
                $callback = function() use($status) {
                    if(is_string($status))
                        EQ::app()->redirect($status);
                    else
                        throw new HttpException($status);
                };
            }
            if($actions !== "#all") {
                $actions = explode(",", $actions);
                array_walk($actions, $trimf);
            }
            if($perm === "allow") {
                if($actions === "#all" || in_array(EQ::app()->action_name, $actions))
                    return;
            }
            elseif($perm === "deny") {
                if($actions === "#all" || in_array(EQ::app()->action_name, $actions))
                    $callback();
            }
            else
                throw new ControllerException("Invalid permissions");
        }
    }

    protected function redir($url, $status = 302, $message = "Found")
    {
        EQ::app()->redirect($url, $status, $message);
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
        $this->beforeRender();
        EQ::app()->trigger("beforeRender");
        self::$__view_vars__ = $view_vars;
        $view_file = $this->findViewFile($view);
        if(!$view_file)
            throw new ControllerException("View file not found: $view");
        $content = ViewRenderer::renderFile($view_file, $view_vars);
        EQ::app()->trigger("beforeEcho");
        $this->beforeEcho();
        if($this->template) {
            $out = $this->renderTemplate($this->findTemplate());
            EQ::app()->trigger("client_script.render");
            $out = preg_replace('/\{\{\$HEAD_CONTENT\}\}/',
                EQ::app()->client_script->renderHead(), $out, 1);
            $out = preg_replace('/\{\{\$BODY_BEGIN_CONTENT\}\}/',
                EQ::app()->client_script->renderBegin(), $out, 1);
            $out = preg_replace('/\{\{\$BODY_END_CONTENT\}\}/',
                EQ::app()->client_script->renderEnd(), $out, 1);
            $out = preg_replace('/\{\{\$PAGE_CONTENT\}\}/', $content, $out, 1);
            echo $out;
        }
        else
            echo $content;
        exit;
    }

    protected function findViewFile($view_file)
    {
        if(strpos($view_file, '/') === false)
            $view_file = EQ::app()->controller_name.'/'.$view_file;
        if(file_exists($view_file))
            return $view_file;
        if(file_exists(APPROOT."/views/$view_file.php"))
            return APPROOT."/views/$view_file.php";
        if(file_exists(APPROOT."/views/$view_file.twig"))
            return APPROOT."/views/$view_file.twig";
        if(file_exists(EQROOT."/views/$view_file.php"))
            return EQROOT."/views/$view_file.php";
        if(file_exists(EQROOT."/views/$view_file.twig"))
            return EQROOT."/views/$view_file.twig";
        return false;
    }

    protected function findTemplate()
    {
        if(!$this->template)
            return false;
        if(EQ::isAlias($this->template))
            return EQ::getAlias($this->template);
        if(file_exists($this->template))
            return $this->template;
        $fname = APPROOT."/templates/{$this->template}.php";
        file_exists($fname) or $fname = EQROOT."/templates/{$this->template}.php";
        if(file_exists($fname))
            return $fname;
        else
            throw new ControllerException("Template not found: {$this->template}");
    }

    protected function renderTemplate($fname)
    {
        ob_start();
        require $fname;
        return ob_get_clean();
    }

}

