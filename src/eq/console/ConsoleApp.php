<?php

namespace eq\console;

use EQ;
use eq\base\AppBase;
use eq\base\Loader;
use eq\base\UncaughtExceptionException;
use eq\cgen\base\docblock\TagList;
use eq\helpers\Arr;
use eq\helpers\C;
use eq\helpers\Console;
use eq\datatypes\DataTypeBase;
use eq\base\ExceptionBase;
use eq\helpers\Str;
use eq\php\ErrorException;
use Exception;

/**
 * @property Args args
 * @property int argc
 * @property array argv
 * @property string executable
 * @property string command_name
 * @property string action_name
 * @property array action_options
 */
class ConsoleApp extends AppBase
{

    protected $argc;
    protected $argv;
    protected $executable;
    protected $command_name;
    protected $action_name;
    protected $action_options = [];

    /**
     * @var Command[]
     */
    protected $commands = [];

    public function __construct($config)
    {
        $this->argv = Arr::getItem($_SERVER, "argv", []);
        $this->argc = Arr::getItem($_SERVER, "argc", count($this->argv));
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
        if(($cname = $this->args->option("actions")))
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
                            "Invalid option value: ".$opt->name." ($val)");
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
            if($param->multi) {
                $args = $this->args->arguments($i);
                count($args) or $args = $def;
                $params[] = $args;
                break;
            }
            if($def === null && $val === null) {
                $params[] = null;
            }
            else {
                $type = DataTypeBase::getClass($param->type);
                if(!is_null($val) && !$type::validate($val))
                    return $this->printMessage(
                        "Invalid argument value: ".$param->name.(is_scalar($val) ? " ($val)" : ""));
                $params[] = $type::filter($val);
            }
            $i++;
        }
        try {
            $this->handleSignal($action);
            return call_user_func_array(
                [$cmdclass::inst(), $action->name], $params);
        }
        catch(ExceptionBase $e) {
            $this->processException($e);
            return -1;
        }
        catch(Exception $ue) {
            $this->processUncaughtException($ue);
            return -1;
        }
    }

    public function processFatalError(array $err)
    {
        $this->processException(
            new ErrorException($err['type'], $err['message'], $err['file'], $err['line'], [])
        );
    }

    public function processException(ExceptionBase $e)
    {
        $this->trigger("exception", $e);
        $ns = Str::classNamespace($e);
        if($ns)
            $ns .= "\\";
        $trace = method_exists($e, "_getTrace") ? $e->_getTrace() : $e->getTrace();
        C::renderErr("%1%R{{ %a%$%n%$ }}%0", $ns, Str::classBasename($e));
        C::renderErr("%dCode:     %b%$%n", $e->getCode());
        C::renderErr("%dMessage:  %0%$", $e->getMessage());
        C::renderErr("%dLocation: %c%1%$%0 line %c%1%$%0\n",
            EQ::unalias($e->getFile()), $e->getLine());
        C::renderErr("%B%1{{Stack trace: }}%0\n");
        foreach($trace as $i => $call) {
            C::stderr(C::render('%d@3{{\#%$ }}%n ', $i), false);
            if(isset($call['file'], $call['line'])) {
                C::renderErr("%y%$%n line %y%$%n", EQ::unalias($call['file']), $call['line']);
            }
            else {
                C::stderr("...");
            }
            $line = "      ";
            if(isset($call['class'], $call['type']))
                $line .= C::seq(C::FG_LIGHT_BLUE).$call['class'].C::seq(C::FG_GRAY).$call['type'].C::seq();
            $line .= C::seq(C::FG_LIGHT_GREEN).$call['function'].C::seq();
            if($call['args']) {
                $line .= "(\n";
                $args = [];
                foreach($call['args'] as $arg)
                    $args[] = C::shortDump($arg, 8, C::width() - 9);
                $line .= implode(C::seq(C::FG_GRAY).",".C::seq()."\n", $args)."\n";
                $line .= C::seq(C::FG_GRAY)."      )".C::seq();
            }
            else
                $line .= C::seq(C::FG_GRAY)."()".C::seq();
            C::stderr($line."\n");
        }
        C::renderErr("%d{{ ".EQ::powered()."}}%0");
    }

    public function processUncaughtException(Exception $e)
    {
        $this->processException(
            new UncaughtExceptionException($e)
        );
    }
    
    protected function scanCommands()
    {
        $this->scanCommandsDir("@appsrc/commands", $this->app_namespace.'\commands');
        $this->scanCommandsDir("@eqsrc/commands", 'eq\commands');
        foreach($this->enabled_modules as $module) {
            $ns = $module->namespace.'\commands';
            $this->scanCommandsDir($module->location."/commands", $ns);
        }
    }

    protected function scanCommandsDir($dir, $ns)
    {
        /**
         * @var string|Command $cname
         */
        $dir = self::getAlias($dir);
        foreach(array_filter(glob($dir."/*Command.php"), "is_file") as $fname) {
            $cbasename = preg_replace('/\.php$/', "", basename($fname));
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
        $msg = C::render(
            "%yUsage:%0\n".
            "  %$ <command> <action>\n\n".
            "%yOptions:%0\n".
            "  @20{{%g--commands%0 }} Show available commands\n".
            "  @20{{%g--actions%0 }} Show available actions\n".
            "  @20{{%g--pure-print%0 }} Print items through space (for autocomplete)\n\n".
            "%d{{ ".EQ::powered()."}}%0",
            $this->argv[0]);
        return $this->printMessage($msg, $err);
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
                $out[] = C::fmt($cmdname, C::FG_GREEN)."\n    ".$descr."\n";
            }
            echo implode("\n", $out);
            C::renderOut("\n%d{{ ".EQ::powered()."}}%0");
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
            $actlines = [
                C::fmt($actname, C::FG_RED, C::BOLD)." ".
                C::fmt($action->parameters_str, C::FG_CYAN)." ".
                C::fmt($action->options_str, C::FG_BLUE)
            ];
            $descr = $action->short_description;
            $descr = $descr ? str_replace("\n", "\n    ", $descr) : "* No description *";
            $actlines[] = "  ".$descr;
            if($action->parameters) {
                $lines = [C::fmt("  Parameters:", C::FG_YELLOW)];
                foreach($action->parameters as $param) {
                    $descr = $param->description;
                    $descr = $descr
                        ? preg_replace('/\n\s*/', "\n                      ", $descr)
                        : "* No description *";
                    $lines[] = C::render("    @18{{%g%$%0 }}$descr", $param->name);
                }
                $actlines[] = implode("\n", $lines);
            }
            if($action->options) {
                $lines = [C::fmt("  Options:", C::FG_YELLOW)];
                foreach($action->options as $opt) {
                    $descr = $opt->description;
                    $descr = $descr
                        ? preg_replace('/\n\s*/', "\n                      ", $descr)
                        : "* No description *";
                    $lines[] = C::render("    @18{{%g%$%0 }}$descr", $opt->name);
                }
                $actlines[] = implode("\n", $lines);
            }
            $out[] = implode("\n\n", $actlines);
        }
        echo implode("\n\n\n", $out)."\n";
        C::renderOut("\n%d{{ ".EQ::powered()."}}%0");
        return 0;
    }

    protected function handleSignal(ReflectionAction $action)
    {
        $signals = [
            SIGABRT,
            SIGALRM,
            SIGBUS,
            SIGCHLD,
            SIGCONT,
            SIGFPE,
            SIGHUP,
            SIGILL,
            SIGINT,
            SIGQUIT,
            SIGSEGV,
            SIGTERM,
            SIGTSTP,
            SIGTTIN,
            SIGTTOU,
            SIGUSR1,
            SIGUSR2,
            SIGPOLL,
            SIGPROF,
            SIGSYS,
            SIGTRAP,
            SIGURG,
            SIGVTALRM,
            SIGXCPU,
            SIGXFSZ,
        ];
        $tags = $action->docblock->tag("signal")->assoc(TagList::A_WFIRST, TagList::A_WSECOND);
        declare(ticks = 1);
        foreach($tags as $const => $method) {
            $signo = constant($const);
            if(!in_array($signo, $signals, true)) {
                EQ::warn($action->command_class
                    ."::".$action->name."(): Unknown or unsupported signal: $const");
                continue;
            }
            if(!is_callable([$action->command, $method])) {
                EQ::warn($action->command_class
                    ."::".$action->name."(): Invalid signal handler: $method");
                continue;
            }
            pcntl_signal($signo, [$action->command, $method]);
        }
    }

}
