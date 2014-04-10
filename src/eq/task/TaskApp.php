<?php
/**
 * Last Change: 2014 Mar 14, 15:00
 */

namespace eq\task;

use eq\helpers\Str;
use eq\helpers\Arr;
use eq\helpers\Console;
use eq\base\LoaderException;

class TaskApp extends \eq\base\AppBase
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
    }

    public function processFatalError($err)
    {
        // TODO Implement
        echo "Fatal Error:\n";
        print_r($err);
    }

    public function processUncaughtException($e)
    {
        // TODO Implement
        echo get_class($e).": ".$e->getMessage()."\n\n";
        echo $e->getTraceAsString()."\n";
    }

    protected function runTask($task_class, $task_args)
    {
        try {
            if(!is_subclass_of($task_class, 'eq\task\TaskBase'))
                throw new TaskException(
                    "Task class must be a subclass of eq\\task\\TaskBase");
            $task = new $task_class();
            return $task->runNow($task_args);
        }
        catch(LoaderException $e) {
            throw new TaskException($e->getMessage());
        }
    }

}
