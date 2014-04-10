<?php

namespace eq\console;

use EQ;
use eq\base\LoaderException;
use eq\base\console\CommandException;
use eq\helpers\Str;

class ReflectionCommand extends \ReflectionClass
{

    private $instance = null;

    public function __construct($command)
    {
        $cbasename = Str::cmd2method($command).'Command';
        try {
            $cname = '\eq\commands\\'.$cbasename;
            $this->instance = new $cname($this);
        }
        catch(LoaderException $e) {}
        try {
            $cname = EQ::app()->app_namespace.'\commands\\'.$cbasename;
            $this->instance = new $cname($this);
        }
        catch(LoaderException $e) {}
        if($this->instance)
            parent::__construct($this->instance);
    }

    public function exists()
    {
        return !\is_null($this->instance);
    }

    public function getActions($docstr = false)
    {
        $methods = \get_class_methods($this->instance);
        $actions  = [];
        foreach($methods as $method) {
            if(\strlen($method) > 6 && \substr($method, 0, 6) === 'action') {
                $actname = Str::method2cmd(\substr($method, 6));
                if($docstr) {
                    $action = new ReflectionAction($this->instance, $method);
                    $params = $action->getParamsDoc();
                    $descr = $action->getDescription();
                    if($params) $actname .= " $params";
                    if($descr) $actname.= " - $descr";
                }
                $actions[] = $actname;
            }
        }
        return $actions;
    }

    public function getAction($action)
    {
        $method = 'action'.Str::cmd2method($action);
        if(!\method_exists($this->instance, $method))
            return false;
        return new ReflectionAction($this->instance, $method);
    }

    public function getDefaultAction()
    {
        // TODO Implement
    }

    public static function getCommands()
    {
        $app_files = [];
        foreach(EQ::app()->config("system.src_dirs", []) as $dir) {
            $app_files = array_merge($app_files, 
                array_filter(glob(EQ::getAlias("$dir/*/commands/*Command.php")), "is_file"));
        }
        $app_commands = self::scanCommandFiles($app_files, EQ::app()->app_namespace.'\commands');
        $eq_files = array_filter(glob(EQROOT."/src/eq/commands/*Command.php"), "is_file");
        $eq_commands = self::scanCommandFiles($eq_files, 'eq\commands');
        return array_merge($app_commands, $eq_commands);
    }

    public function getDocDescription()
    {
        $comment = $this->getDocComment();
        if(!$comment) return '';
        $lines = \preg_split("/[\r\n]+/", $comment);
        return isset($lines[1]) ? preg_replace("/[\s\t]*\*[\s\t]+/", '', $lines[1]) : '';
    }

    private static function scanCommandFiles($files, $namespace)
    {
        $commands = [];
        foreach($files as $file) {
            $cbasename = preg_replace('/\.php$/', '', basename($file));
            $cname = $namespace.'\\'.$cbasename;
            require_once $file;
            if(class_exists($cname, false) && is_subclass_of($cname, 'eq\console\Command'))
                $commands[] = Str::method2cmd(preg_replace('/Command$/', '', $cbasename));
        }
        return $commands;
    }

}
