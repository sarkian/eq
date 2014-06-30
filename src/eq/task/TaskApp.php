<?php

namespace eq\task;

use eq\base\AppBase;
use eq\base\ExceptionBase;
use eq\base\Loader;
use eq\base\UncaughtExceptionException;
use eq\console\Args;
use eq\helpers\Arr;
use eq\helpers\C;
use eq\helpers\FileSystem;
use eq\php\ErrorException;
use Exception;

/**
 * @property Args args
 * @property int argc
 * @property array argv
 * @property string executable
 */
class TaskApp extends AppBase
{

    protected $argc = 0;
    protected $argv = [];
    protected $executable;

    /**
     * @var string|TaskBase
     */
    protected $task_class;
    protected $task_args;

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

    public function run()
    {
        $mode = $this->args->argument(0);
        if($mode !== "sync" && $mode !== "async") {
            C::stderr("Invalid argument: mode. Supported values: sync, async");
            return -1;
        }
        $this->task_class = $this->args->argument(2);
        if(!Loader::classExists($this->task_class)) {
            C::stderr("Task class not found: ".$this->task_class);
            return -1;
        }
        if(!isset(class_parents($this->task_class)['eq\task\TaskBase'])) {
            C::stderr('Task class must be a subclass of eq\task\TaskBase: '.$this->task_class);
            return -1;
        }
        $this->task_args = @unserialize($this->args->argument(3));
        if(!is_array($this->task_args)) {
            C::stderr("Invalid arguments: args. Expected: serialized array");
            return -1;
        }
        return $mode === "sync" ? $this->runSync() : $this->runAsync();
    }

    protected function runSync()
    {
        try {
            $task = $this->taskInst();
            $this->handleSignal($task);
            return $task->runNow($this->task_args);
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

    protected function runAsync()
    {
        $mode = $this->args->argument(4);
        if($mode === null) {
            C::stderr("Missing argument: mode");
            return -1;
        }
        $mode = (int) $mode;
        if($mode < 0 || $mode > 3) {
            C::stderr('Invalid argument: mode. See eq\task\TaskBase constants.');
            return -1;
        }
        $task_class = $this->task_class;
        $task_class::_run($this->task_args, (int) $this->argv[4], [
            'outlog' => $this->args->option("outlog"),
            'errlog' => $this->args->option("errlog"),
            'append_outlog' => $this->args->option("append-outlog", false),
            'append_errlog' => $this->args->option("append-errlog", false),
        ]);
        return 0;
    }

    public function processFatalError(array $err)
    {
        $this->processException(
            new ErrorException($err['type'], $err['message'], $err['file'], $err['line'], [])
        );
    }

    public function processException(ExceptionBase $e)
    {
        // TODO: Implement
        $this->trigger("exception", $e);
        echo get_class($e)."\n"
            .$e->getMessage()."\n"
            .$e->getTraceAsString()."\n";
    }

    public function processUncaughtException(Exception $e)
    {
        $this->processException(
            new UncaughtExceptionException($e)
        );
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

    /**
     * @return TaskBase
     */
    protected function taskInst()
    {
        $cname = $this->task_class;
        return new $cname();
    }

    protected function handleSignal(TaskBase $task)
    {
        $signals = [
            SIGABRT => "sigabrt",
            SIGALRM => "sigalrm",
            SIGBUS => "sigbus",
            SIGCHLD => "sigchld",
            SIGCONT => "sigcont",
            SIGFPE => "sigfpe",
            SIGHUP => "sighup",
            SIGILL => "sigill",
            SIGINT => "sigint",
            SIGQUIT => "sigquit",
            SIGSEGV => "sigsegv",
            SIGTERM => "sigterm",
            SIGTSTP => "sigtstp",
            SIGTTIN => "sigttin",
            SIGTTOU => "sigttou",
            SIGUSR1 => "sigusr1",
            SIGUSR2 => "sigusr2",
            SIGPOLL => "sigpoll",
            SIGPROF => "sigprof",
            SIGSYS => "sigsys",
            SIGTRAP => "sigtrap",
            SIGURG => "sigurg",
            SIGVTALRM => "sigvtalrm",
            SIGXCPU => "sigxcpu",
            SIGXFSZ => "sigxfsz",
        ];
        $sig_noint = [
            SIGCHLD,
            SIGCONT,
            SIGTSTP,
            SIGTTIN,
            SIGTTOU,
            SIGURG,
        ];
        declare(ticks = 1);
        foreach($signals as $signo => $method) {
            if(in_array($signo, $sig_noint)) {
                $callback = function() use($task, $method) {
                    if(is_callable([$task, $method]))
                        call_user_func([$task, $method]);
                };
            }
            else {
                $callback = function() use($task, $method) {
                    if(is_callable([$task, $method])) {
                        call_user_func([$task, $method]);
                    }
                    else {
                        self::warn("Unhandled signal: ".strtoupper($method));
                        exit;
                    }
                };
            }
            pcntl_signal($signo, $callback);
        }
    }

    public function __destruct()
    {
        $file = TaskBase::getRunFile();
        if(file_exists($file))
            FileSystem::rm($file);
    }

}
