<?php

/**
 * Last Change: 2014 Apr 14, 17:48
 */

namespace eq\console;

use EQ;
use eq\base\Loader;
use eq\base\LoaderException;
use eq\base\console\InvalidOptionException;
use eq\helpers\Arr;
use eq\helpers\Console;
use eq\helpers\Str;
use eq\datatypes\DataTypeBase;
use eq\base\ExceptionBase;
use Exception;

class ConsoleApp extends \eq\base\AppBase
{

    protected $argc;
    protected $argv;
    protected $executable;
    protected $command_name;
    protected $action_name;
    protected $action_options = [];

    protected $commands = [];

    public function __construct($config)
    {
        $this->argc = Arr::getItem($_SERVER, "argc", 0);
        $this->argv = Arr::getItem($_SERVER, "argv", []);
        $this->executable = realpath($this->argv[0]);
        parent::$_app = $this;
        parent::__construct($config);
    }

    public function getArgc()
    {
        return $this->argc;
    }

    public function getArgv()
    {
        return $this->argv;
    }

    public function getExecutable()
    {
        return $this->executable;
    }

    public function getCommandName()
    {
        return $this->command_name;
    }

    public function getActionName()
    {
        return $this->action_name;
    }

    public function getActionOptions()
    {
        return $this->action_options;
    }

    public function run()
    {
        if($this->args->option(["h", "help"], false))
            return $this->printUsage();
        $this->scanCommands();
        if($this->args->option("commands", false))
            return $this->printCommands();
        if($cname = $this->args->option("actions"))
            return $this->printActions($cname);
        $this->command_name = $this->args->argument(0);
        $this->action_name = $this->args->argument(1, "default");
        if(!$this->command_name)
            return $this->printUsage(true);
        if(!isset($this->commands[$this->command_name]))
            return $this->printMessage("Unknown command: ".$this->command_name);
        $cmdclass = $this->commands[$this->command_name];
        if(!$cmdclass::reflect()->actionExists($this->action_name))
            return $this->printMessage("Unknown action: ".$this->action_name);
        $action = $cmdclass::reflect()->getAction($this->action_name);
        foreach($action->options as $opt) {
            if($opt->type == "bool")
                $val = $this->args->option($opt->name, false);
            else {
                $type = DataTypeBase::getClass($opt->type);
                $val = $this->args->option($opt->name);
                if(!is_null($val)) {
                    if(!$type::validate($val))
                        return $this->printMessage(
                            "Invalid option value: ".$opt->name."($val)");
                    $val = $type::filter($val);
                }
            }
            $this->action_options[$opt->name] = $val;
        }
        $i = 2;
        $params = [];
        foreach($action->parameters as $param) {
            $def = $param->isDefaultValueAvailable()
                ? $param->getDefaultValue() : null;
            $val = $this->args->argument($i, $def);
            if($param->required && is_null($val))
                return $this->printMessage("Missed argument: ".$param->name);
            $type = DataTypeBase::getClass($param->type);
            if(!is_null($val) && !$type::validate($val))
                return $this->printMessage(
                    "Invalid argument value: ".$param->name." ($val)");
            $params[] = $type::filter($val);
            $i++;
        }
        try {
            return call_user_func_array(
                [$cmdclass::inst(), $action->name], $params);
        }
        catch(ExceptionBase $e) {
            $this->processException($e);
            return -1;
        }
        catch(Exception $ue) {
            $this->processUncaughtException($e);
            return -1;
        }
    }

    public function processFatalError($err)
    {
        // TODO Implement
        echo "Fatal Error:\n";
        print_r($err);
    }

    public function processException($e)
    {
        // TODO Implement
        echo get_class($e).": ".$e->getMessage()."\n\n";
        echo $e->getTraceAsString();
    }

    public function processUncaughtException($e)
    {
        // TODO Implement
        echo get_class($e).": ".$e->getMessage()."\n\n";
        echo $e->getTraceAsString()."\n";
    }

    protected function scanCommands()
    {
        $this->scanCommandsDir("@appsrc/commands",
            $this->app_namespace.'\commands');
        $this->scanCommandsDir("@eqsrc/commands", 'eq\commands');
        foreach($this->loaded_modules as $mod => $cname) {
            $ns = Str::classNamespace($cname).'\commands';
            $this->scanCommandsDir($cname::location()."/commands", $ns);
        }
    }

    protected function scanCommandsDir($dir, $ns)
    {
        $dir = self::getAlias($dir);
        foreach(array_filter(glob($dir."/*Command.php"), "is_file") as $fname) {
            $cbasename = preg_replace("/\.php$/", "", basename($fname));
            $cname = $ns."\\".$cbasename;
            if(!Loader::classExists($cname))
                continue;
            $cmdname = $cname::commandName();
            if(!isset($this->commands[$cmdname]))
                $this->commands[$cmdname] = $cname;
        }
    }

    protected function systemComponents()
    {
        return array_merge(parent::systemComponents(), [
            'args' => [
                'class' => 'eq\console\Args',
                'preload' => true,
            ],
        ]);
    }

    protected function printMessage($msg, $err = true)
    {
        if($err) {
            Console::stderr($msg);
            return -1;
        }
        else {
            Console::stdout($msg);
            return 0;
        }
    }

    protected function printUsage($err = false)
    {
        $msg = "Usage: {$this->argv[0]} <command> <action>\n".
             "Options:\n".
             "    --commands [--pure-print] -- Available commands\n".
             "    --actions <command> [--pure-print]  -- Available actions";
        return $this->printMessage($msg);
    }

    protected function printCommands()
    {
        if($this->args->option("pure-print", false)) {
            echo implode(" ", array_keys($this->commands));
        }
        else {
            $out = [];
            foreach($this->commands as $cmdname => $cname) {
                $command = $cname::reflect();
                $descr = $command->getShortDescription();
                if($descr)
                    $descr = str_replace("\n", "\n    ", $descr);
                else
                    $descr = "* No description *";
                $out[] = $cmdname."\n    ".$descr."\n";
            }
            echo implode("\n", $out);
        }
        return 0;
    }

    protected function printActions($cname)
    {
        if(!isset($this->commands[$cname])) {
            Console::stderr("Unknown command: $cname");
            return -1;
        }
        $cmdname = $this->commands[$cname];
        $command = $cmdname::reflect();
        if($this->args->option("pure-print", false)) {
            echo implode(" ", array_keys($command->getActions()));
            return 0;
        }
        $out = [];
        foreach($command->getActions() as $actname => $action) {
            $descr = $action->short_description;
            $descr = $descr 
                ? str_replace("\n", "\n    ", $descr) : "* No description *";
            $str = $actname." ".$action->parameters_str
                ." ".$action->options_str."\n    ".$descr."\n";
            if($action->parameters) {
                $lines = ["    Parameters:"];
                foreach($action->parameters as $param) {
                    $descr = $param->description;
                    $descr = $descr
                        ? str_replace("\n", "\n        ", $descr)
                        : "* No description *";
                    $lines[] = "        ".$param->name." - ".$descr;
                }
                $str .= implode("\n", $lines)."\n";
            }
            if($action->options) {
                $lines = ["    Options:"];
                foreach($action->options as $opt) {
                    $descr = $opt->description;
                    $descr = $descr
                        ? str_replace("\n", "\n        ", $descr)
                        : "* No description *";
                    $lines[] = "        ".$opt->name." - ".$descr;
                }
                $str .= implode("\n", $lines)."\n";
            }
            $out[] = $str;
        }
        echo implode("\n", $out);
        return 0;
    }

}
