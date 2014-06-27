<?php
/**
 * Last Change: 2014 Mar 14, 15:00
 */

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

    public function __construct($config)
    {
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

    public function run()
    {
        $this->argv = Arr::getItem($_SERVER['argv'], []);
        $this->argc = Arr::getItem($_SERVER['argc'], count($this->argv));
        $task_class = $this->argv[2];
        $task_args = @unserialize($this->argv[$this->argc - 1]);
        is_array($task_args) or $task_args = [];
        try {
            return $this->runTask($task_class, $task_args);
        }
        catch(TaskException $e) {
            Console::stderr($e->getMessage()."\n");
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

    public function processFatalError(array $err)
    {
        $this->processException(
            new ErrorException($err['type'], $err['message'], $err['file'], $err['line'], [])
        );
    }

    public function processException(ExceptionBase $e)
    {
        // TODO: Implement
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
     * @param string $task_class
     * @param $task_args
     * @return int
     * @throws TaskException
     */
    protected function runTask($task_class, $task_args)
    {
        if(!Loader::classExists($task_class))
            throw new TaskException("Task class not found: $task_class");
        $task = new $task_class();
        if(!$task instanceof TaskBase)
            throw new TaskException(
                'Task class must be a subclass of eq\task\TaskBase: '.$task_class);
        $this->handleSignal($task);
        return $task->runNow($task_args);
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
