<?php
/**
 * Last Change: 2014 Mar 14, 15:09
 */

namespace eq\task;

use eq\base\FileSystemException;
use eq\base\InvalidParamException;
use eq\helpers\Path;
use eq\helpers\FileSystem;
use eq\helpers\System;
use EQ;

abstract class TaskBase
{

    /**
     * @const int Не запускать задачу,
     * если она уже запущена с такими аргументами
     */
    const R_ONCE            = 0;

    /**
     * @const int Не проверять, запущена ли уже эта задача с такими аргументами
     */
    const R_FORCE           = 1;

    /**
     * @const int Перезапустить задачу (прибивает по kill -9)
     */
    const R_RESTART         = 2; // перезапуск

    /**
     * @const int Добавить в очередь,
     * если задача с такими аргументами уже выполняется
     */
    const R_QUEUE           = 3; // добавить в очередь

    /**
     * @property eq\task\TaskQueue $queue Очередь
     * @see getQueue()
     */
    protected static $queue = null;

    /**
     * Содержит тело задачи
     * 
     * @param array $args 
     * @return int
     */
    abstract protected function __run(array $args = []);

    /**
     * Запускает задачу синхронно с аргументами $args
     * 
     * @param array $args 
     * @return void
     */
    public final function runNow(array $args = [])
    {
        $run_file = self::getRunFile();
        FileSystem::fputs($run_file, serialize([
            'task_args' => $args,
            'proc_args' => System::procGetArgs(getmypid()),
        ]));
        $res = $this->__run($args);
        FileSystem::rm($run_file);
        $queue = self::getQueue();
        if($queue->count($args)) {
            $task = $queue->shift($args);
            $queue->clear($args)->save();
            self::_run($task['args'],
                self::R_FORCE, $task['outlog'], $task['errlog']);
        }
        return is_int($res) ? $res : 0;
    }

    /**
     * Запускает задачу асинхронно. Рекомендуется определять свой метод run()
     * для каждой задачи (именно поэтому он с префиксом '_')
     * и в нём уже определять необходимые параметры и вызывать _run().
     *  
     * @param array     $args       Аргументы задачи
     * @param int       $run        Как запускать (см. константы)
     * @param string    $outlog     Лог STDOUT
     * @param string    $errlog     Лог STDERR
     */
    public static function _run(array $args = [], $run = self::R_ONCE,
        $outlog = null, $errlog = null)
    {
        if($outlog) {
            $outlog = EQ::getAlias($outlog);
            FileSystem::mkdir(dirname($outlog));
        }
        else
            $outlog = "/dev/null";
        if($errlog) {
            $errlog = EQ::getAlias($errlog);
            FileSystem::mkdir(dirname($errlog));
        }
        self::normalizeArgs($args);
        switch($run) {
            case self::R_ONCE:
                if(self::runningCount($args))
                    return;
                break;
            case self::R_FORCE:
                break;
            case self::R_RESTART:
                self::kill($args);
                break;
            case self::R_QUEUE:
                $queue = self::getQueue();
                if(self::runningCount($args)) {
                    if(!$queue->count($args))
                        $queue->append($args, $outlog, $errlog)->save();
                    return;
                }
                elseif($queue->count($args))
                    $queue->clear()->save();
                break;
            default:
                throw new InvalidParamException(
                    "Parameter 'run' must be one of TaskBase::R_* constants");
        }
        FileSystem::fputs("@runtime/config.s", serialize(EQ::app()->config()));
        exec("exec nohup setsid ".self::getRunCommand($args)
            ." > ".$outlog." 2>".$errlog." &");
    }

    public static function waitForComplete(array $args = [])
    {
        // TODO implement
    }

    public static function normalizeArgs(array $args = [])
    {
        array_walk($args, function(&$arg) { $arg = (string) $arg; });
        return $args;
    }

    public static function getRunningPids(array $args = [])
    {
        $run_files = array_filter(glob(self::getRunFileMask()), "is_file");
        $procs = System::procGetAll();
        array_walk($args, function(&$arg) { $arg = (string) $arg; });
        $args_str = serialize($args);
        $pids = [];
        foreach($run_files as $file) {
            $fname = explode(":", basename($file), 2);
            if(count($fname) !== 2)
                continue;
            $pid = (int) $fname[1];
            if(!$pid)
                continue;
            if(isset($procs[$pid])) {
                if(self::compareRunFile($file, $pid, $args_str))
                    $pids[] = $pid;
            }
            else
                FileSystem::rm($file);
        }
        return $pids;
    }

    public static function getRunningPidsAll()
    {
        $run_files = array_filter(glob(self::getRunFileMask()), "is_file");
        $procs = System::procGetAll();
        $pids = [];
        foreach($run_files as $file) {
            $fname = explode(":", basename($file), 2);
            if(count($fname) !== 2)
                continue;
            $pid = (int) $fname[1];
            if(!$pid)
                continue;
            if(isset($procs[$pid]))
                $pids[] = $pid;
            else
                FileSystem::rm($file);
        }
        return $pids;
    }

    public static function runningCount(array $args = [])
    {
        return count(self::getRunningPids($args));
    }

    public static function runningCountAll()
    {
        return count(self::getRunningPidsAll());
    }

    public static function getQueue()
    {
        return new TaskQueue(self::getQueueFile());
    }

    public static function kill(array $args = [])
    {
        foreach(self::getRunningPids($args) as $pid)
            self::killByPid($pid);
    }

    public static function killAll()
    {
        foreach(self::getRunningPidsAll() as $pid)
            self::killByPid($pid);
    }

    protected static function killByPid($pid)
    {
        System::procKill($pid);
        $file = self::getRunFile($pid);
        if(file_exists($file))
            FileSystem::rm($file);
    }

    protected static function getRunDir()
    {
        $tmp = sys_get_temp_dir();
        if(!is_dir($tmp) || !is_writable($tmp))
            $tmp = Path::join([APPROOT, "runtime"]);
        $run_dir = Path::join([$tmp, "eqtask"]);
        if(!is_dir($run_dir)) {
            if(file_exists($run_dir))
                FileSystem::rm($run_dir);
            FileSystem::mkdir($run_dir);
        }
        FileSystem::assertWritable($run_dir);
        if(fileperms($run_dir) !== 0775)
            @chmod($run_dir, 0775);
        return $run_dir;
    }

    protected static function getRunFileMask()
    {
        $cname = get_called_class();
        return Path::join([
            self::getRunDir(),
            str_replace("\\", ".", $cname).":*",
        ]);
    }

    protected static function getRunCommand(array $args)
    {
        return implode(" ", [
            Path::join([EQROOT, "bin", "run_task"]),
            EQ::getAlias("@runtime/config.s"),
            escapeshellarg(get_called_class()),
            escapeshellarg(serialize($args)),
        ]);
    }

    protected static function compareRunFile($fname, $pid, $args_str)
    {
        try {
            $data = unserialize(FileSystem::fgets($fname));
            if(!isset($data['task_args'], $data['proc_args']))
                return false;
            if(!is_array($data['task_args']) || !is_array($data['proc_args']))
                return false;
            $proc_args = System::procGetArgs($pid);
            $proc_task_args = $proc_args[count($proc_args) - 1];
            if($data['proc_args'] === $proc_args && $proc_task_args === $args_str)
                return true;
            return false;
        }
        catch(FileSystemException $e) {
            return false;
        }
    }

    protected static function getRunFile($pid = null)
    {
        $cname = get_called_class();
        $pid or $pid = getmypid();
        return Path::join([
            self::getRunDir(),
            str_replace("\\", ".", $cname).":".$pid,
        ]);
    }

    protected static function getQueueFile()
    {
        $cname = get_called_class();
        return Path::join([
            self::getRunDir(),
            str_replace("\\", ".", $cname).".queue",
        ]);
    }

}
