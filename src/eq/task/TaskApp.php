<?php

namespace eq\task;

use eq\base\AppBase;
use eq\base\ExceptionBase;
use eq\base\Loader;
use eq\base\UncaughtExceptionException;
use eq\helpers\Arr;
use eq\helpers\Console;
use eq\helpers\FileSystem;
use eq\php\ErrorException;
use Exception;

class TaskApp extends AppBase
{

    protected $argc = 0;
    protected $argv = [];
    /**
     * @var string|TaskBase
     */
    protected $task_class;
    protected $task_args;

    public function __construct($config)
    {
        parent::__construct($config);
        $this->argv = Arr::getItem($_SERVER['argv'], []);
        $this->argc = Arr::getItem($_SERVER['argc'], count($this->argv));
        $this->task_class = $this->argv[2];
        $this->task_args = @unserialize($this->argv[3]);
        is_array($this->task_args) or $this->task_args = [];
    }

    public function getArgc()
    {
        return $this->argc;
    }

    public function getArgv()
    {
        return $this->argv;
    }

    public function run()
    {
        try {
            return $this->runTask();
        }
        catch(TaskException $e) {
            Console::stderr($e->getMessage());
            return -1;
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

    public function runAsync()
    {
        $task_class = $this->task_class;
        $task_class::_run($this->task_args, (int) $this->argv[4], [
            'outlog' => $this->argv[5],
            'errlog' => $this->argv[6],
            'append_outlog' => (bool) $this->argv[7],
            'append_errlog' => (bool) $this->argv[8],
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

    /**
     * @return int
     * @throws TaskException
     */
    protected function runTask()
    {
        $task_class = $this->task_class;
        if(!Loader::classExists($task_class))
            throw new TaskException("Task class not found: $task_class");
        $task = new $task_class();
        if(!$task instanceof TaskBase)
            throw new TaskException(
                'Task class must be a subclass of eq\task\TaskBase: '.$task_class);
        $this->handleSignal($task);
        return $task->runNow($this->task_args);
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
