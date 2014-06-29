<?php
/**
 * Last Change: 2014 Apr 15, 21:09
 */

namespace eq\task;

use EQ;
use eq\base\Loader;
use eq\base\FileSystemException;
use eq\base\InvalidParamException;
use eq\helpers\Arr;
use eq\helpers\Path;
use eq\helpers\FileSystem;
use eq\helpers\Str;
use eq\helpers\System;
use eq\helpers\Shell;

abstract class TaskBase
{

    /**
     * @const int Не запускать задачу, если она уже запущена с такими аргументами
     */
    const R_ONCE            = 0;

    /**
     * @const int Не проверять, запущена ли уже эта задача с такими аргументами
     */
    const R_FORCE           = 1;

    /**
     * @const int Перезапустить задачу (прибивает по kill -9)
     */
    const R_RESTART         = 2;

    /**
     * @const int Добавить в очередь, если задача с такими аргументами уже выполняется
     */
    const R_QUEUE           = 3;

    /**
     * @property TaskQueue $queue Очередь
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
     * Возвращает имя класса
     *
     * @return string
     */
    public static final function className()
    {
        return get_called_class();
    }

    /**
     * Возвращает имя класса по имени задачи
     *
     * @param string $taskname Имя задачи
     * @return static|bool Имя класса задачи или false, если задачи не существует
     */
    public static final function getClass($taskname)
    {
        return Loader::autofindClass($taskname, "tasks");
    }

    /**
     * @return static
     */
    public static final function instance()
    {
        $cname = get_called_class();
        return new $cname();
    }

    /**
     * Запускает задачу синхронно с аргументами $args

     * @param array $args
     * @return int
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

    public function taskName()
    {
        return Str::method2var(preg_replace("/Task$/", "", Str::classBasename($this)));
    }

    public function defaultOutLog()
    {
        return "@log/".$this->taskName().".out.log";
    }

    public function defaultErrLog()
    {
        return "@log/".$this->taskName().".err.log";
    }

    /**
     * Запускает задачу асинхронно
     *
     * Рекомендуется определять свой метод run()
     * для каждой задачи (именно поэтому он с префиксом '_')
     * и в нём уже определять необходимые параметры и вызывать _run().
     *
     * @param array $args Аргументы задачи
     * @param int $run Как запускать (см. константы)
     * @param array $options
     * @throws \eq\base\InvalidParamException
     * @throws \eq\base\ShellExecException
     */
    public static function _run(array $args = [], $run = self::R_ONCE, array $options = [])
    {
        $args = static::normalizeArgs($args);
        $options = static::normalizeOptions($options);
        switch($run) {
            case self::R_ONCE:
                if(static::runningCount($args))
                    return;
                break;
            case self::R_FORCE:
                break;
            case self::R_RESTART:
                self::kill($args, SIGKILL);
                break;
            case self::R_QUEUE:
                $queue = static::getQueue();
                if(self::runningCount($args)) {
                    if(!$queue->count($args))
                        $queue->append($args, $options)->save();
                    return;
                }
                elseif($queue->count($args))
                    $queue->clear()->save();
                break;
            default:
                throw new InvalidParamException(
                    "Parameter 'run' must be one of TaskBase::R_* constants");
        }
        $cmd = "exec nohup setsid ".self::getFullRunCommand($args, $options)." &";
        Shell::exec($cmd);
    }

    public static function waitForComplete(array $args = [], $limit = 0)
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
        $my_pid = getmypid();
        foreach($run_files as $file) {
            $fname = explode(":", basename($file), 2);
            if(count($fname) !== 2)
                continue;
            $pid = (int) $fname[1];
            if(!$pid || $pid === $my_pid)
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
        $my_pid = getmypid();
        foreach($run_files as $file) {
            $fname = explode(":", basename($file), 2);
            if(count($fname) !== 2)
                continue;
            $pid = (int) $fname[1];
            if(!$pid || $pid === $my_pid)
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

    public static function kill(array $args = [], $sig = SIGINT)
    {
        foreach(self::getRunningPids($args) as $pid)
            self::killByPid($pid, $sig);
    }

    public static function killAll($sig = SIGINT)
    {
        foreach(self::getRunningPidsAll() as $pid)
            self::killByPid($pid, $sig);
    }

    public static function getRunCommand(array $args = [])
    {
        return implode(" ", [
            "php",
            Shell::escapeArg(Path::join([EQROOT, "bin", "run_task"])),
            Shell::escapeArg(EQ::getAlias("@app/config.php")),
            escapeshellarg(get_called_class()),
            escapeshellarg(serialize(static::normalizeArgs($args))),
        ]);
    }

    public static function getRunAsyncCommand(array $args = [], $run = self::R_ONCE, $options = [])
    {
        $options = static::normalizeOptions($options);
        return implode(" ", [
            static::getRunCommand($args),
            Shell::escapeArg((int) $run),
            Shell::escapeArg(static::normalizeOutLog($options['outlog'])),
            Shell::escapeArg(static::normalizeErrLog($options['errlog'])),
            $options['append_outlog'] ? "1" : "0",
            $options['append_errlog'] ? "1" : "0",
        ]);
    }

    public static function getFullRunCommand(array $args = [], $options = [])
    {
        $options = static::normalizeOptions($options);
        $outlog = Shell::escapeArg(static::normalizeOutLog($options['outlog']));
        $errlog = Shell::escapeArg(static::normalizeErrLog($options['errlog']));
        $outop = $options['append_outlog'] ? ">>" : ">";
        $errop = $options['append_errlog'] ? ">>" : ">";
        return self::getRunCommand($args)." $outop$outlog 2$errop$errlog";
    }

    public static function getRunFile($pid = null)
    {
        $cname = get_called_class();
        $pid or $pid = getmypid();
        return Path::join([
            self::getRunDir(),
            str_replace("\\", ".", $cname).":".$pid,
        ]);
    }

    protected static function normalizeOutLog($outlog)
    {
        if($outlog === null)
            $outlog = static::instance()->defaultOutLog();
        $outlog = EQ::getAlias($outlog);
        $dir = dirname($outlog);
        if($dir !== ".")
            FileSystem::mkdir($dir);
        return $outlog;
    }

    protected static function normalizeErrLog($errlog)
    {
        if($errlog === null)
            $errlog = static::instance()->defaultErrLog();
        $errlog = EQ::getAlias($errlog);
        $dir = dirname($errlog);
        if($dir !== ".")
            FileSystem::mkdir($dir);
        return $errlog;
    }

    protected static function normalizeOptions($options)
    {
        $options = Arr::extend($options, [
            'outlog' => null,
            'errlog' => null,
            'append_outlog' => false,
            'append_errlog' => false,
        ]);
        $options['outlog'] = static::normalizeOutLog($options['outlog']);
        $options['errlog'] = static::normalizeErrLog($options['errlog']);
        $options['append_outlog'] = (bool) $options['append_outlog'];
        $options['append_errlog'] = (bool) $options['append_errlog'];
        return $options;
    }

    protected static function killByPid($pid, $sig = SIGINT)
    {
        posix_kill($pid, $sig);
        $file = self::getRunFile($pid);
        if(($sig === SIGKILL) && file_exists($file))
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

    protected static function getQueueFile()
    {
        $cname = get_called_class();
        return Path::join([
            self::getRunDir(),
            str_replace("\\", ".", $cname).".queue",
        ]);
    }

}
