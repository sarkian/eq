<?php
/**
 * Last Change: 2014 Mar 14, 15:00
 */

namespace eq\task;

use eq\base\AppBase;
use eq\base\ExceptionBase;
use eq\helpers\Arr;
use eq\helpers\Console;
use eq\base\LoaderException;
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
    }

    public function processFatalError(array $err)
    {
        // TODO Implement
        echo "Fatal Error:\n";
        print_r($err);
    }

    public function processException(ExceptionBase $e)
    {

    }

    public function processUncaughtException(Exception $e)
    {
        // TODO Implement
        echo get_class($e).": ".$e->getMessage()."\n\n";
        echo $e->getTraceAsString()."\n";
    }

    /**
     * @param string $task_class
     * @param $task_args
     * @return int
     * @throws TaskException
     */
    protected function runTask($task_class, $task_args)
    {
        try {
            $task = new $task_class();
            if(!$task instanceof TaskBase)
                throw new TaskException(
                    'Task class must be a subclass of eq\task\TaskBase: '.$task_class);
            return $task->runNow($task_args);
        }
        catch(LoaderException $e) {
            throw new TaskException($e->getMessage());
        }
    }

}
