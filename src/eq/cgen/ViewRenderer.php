<?php

namespace eq\cgen;

use eq\base\LoaderException;
use EQ;

class ViewRenderer
{

    protected static $_has_twig = null;
    protected static $_twig_env = null;

    public static function renderFile($file, $vars = [])
    {
        $parts = explode(".", $file);
        $ext = array_pop($parts);
        if($ext === "twig")
            return self::renderTwigFile($file, $vars);
        else
            return self::renderPhpFile($file, $vars);
    }

    public static function renderPhpFile($__view_file__, $__input_vars_array__ = [])
    {
        ob_start();
        foreach($__input_vars_array__ as $__input_var_name__ => $__input_var_value__) {
            $$__input_var_name__ = $__input_vars_array__[$__input_var_name__];
        }
        require $__view_file__;
        $__view_result_string__ = ob_get_contents();
        ob_end_clean();
        return($__view_result_string__);
    }

    public static function renderTwigFile($file, $vars = [])
    {
        self::twigEnv()->getLoader()->addPath(dirname($file));
        return self::twigEnv()->render($file, $vars);
    }

    public static function hasTwig()
    {
        if(is_null(self::$_has_twig)) {
            try {
                self::$_has_twig = class_exists("Twig_Environment");
            }
            catch(LoaderException $e) {
                self::$_has_twig = false;
            }
        }
        return self::$_has_twig;
    }

    public static function twigEnv()
    {
        if(is_null(self::$_twig_env)) {
            $paths = ["/"];
            if(defined("APPROOT") && is_dir(APPROOT."/views"))
                $paths[] = APPROOT."/views";
            $loader = new \Twig_Loader_Filesystem($paths);
            self::$_twig_env = new \Twig_Environment($loader, [
                'cache' => EQ::getAlias("@runtime/twig"),
                'strict_variables' => true,
                'debug' => EQ_DBG,
            ]);
            self::$_twig_env->addFunction(new \Twig_SimpleFunction("t", ["EQ", "t"]));
            self::$_twig_env->addFunction(new \Twig_SimpleFunction("k", ["EQ", "k"]));
            self::$_twig_env->addFunction(new \Twig_SimpleFunction("createUrl", [EQ::app(), "createUrl"]));
            self::$_twig_env->addFunction(new \Twig_SimpleFunction("widget", ["EQ", "widget"]));
            self::$_twig_env->addGlobal("App", EQ::app());
        }
        return self::$_twig_env;
    }

}
