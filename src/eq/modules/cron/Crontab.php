<?php

namespace eq\modules\cron;

use EQ;
use eq\helpers\Shell;
use eq\helpers\FileSystem;
use eq\task\TaskBase;

class Crontab
{

    protected $user = null;
    protected $pass = null;

    protected $lines = [];
    protected $tasks = [];

    public function __construct($user = null, $pass = null)
    {
        if(is_string($user) && strlen($user) && is_string($pass) && strlen($pass)) {
            $this->user = $user;
            $this->pass = $pass;
        }
        $this->reload();
    }

    public function reload()
    {
        $this->lines = [];
        $this->tasks = [];
        $out = $this->user
            ? Shell::suexec("crontab -l", $this->user, $this->pass) : Shell::exec("crontab -l");
        $lines = preg_split("/[\r\n]/", $out);
        $last_noempty = 0;
        foreach($lines as $i => $line) {
            $this->lines[$i] = $line;
            $line = trim($line, " \r\n\t");
            if(!$line)
                continue;
            $last_noempty = $i;
            if(!strncmp($line, "#", 1))
                continue;
            try {
                $task = new CrontabTask($line);
                $this->lines[$i] = $task;
                $this->tasks[] = $i;
            }
            catch(CrontabException $e) {}
        }
        $this->lines = array_slice($this->lines, 0, $last_noempty + 1);
        return $this;
    }

    public function __toString()
    {
        $str = implode("\n", $this->lines);
        if(substr($str, -2) !== "\n\n")
            $str .= "\n\n";
        return $str;
    }

    public function getTask($taskname, array $args = [])
    {
        $cname = $this->taskClass($taskname);
        $index = $this->getTaskIndex($cname, $args);
        return $index === false ? false : $this->lines[$index];
    }

    public function addTask($taskname, $time, array $args = [])
    {
        $cname = $this->taskClass($taskname);
        if($this->getTaskIndex($cname, $args) !== false)
            throw new CrontabException("Task already exists in crontab: $cname");
        $task = new CrontabTask();
        $task->setTime($time);
        $task->setCommand($cname::getRunCommand($args));
        $i = count($this->lines);
        $this->lines[$i] = $task;
        $this->tasks[] = $i;
        return $this;
    }

    public function removeTask($taskname, array $args = [])
    {
        $cname = $this->taskClass($taskname);
        $cmd = new CrontabTaskCommand($cname::getRunCommand($args));
        $to_remove = [];
        foreach($this->tasks as $i => $index) {
            $task = $this->lines[$index];
            if(!$task->command->equals($cmd))
                continue;
            unset($this->lines[$index]);
            $to_remove[] = $i;
        }
        foreach($to_remove as $i)
            unset($this->tasks[$i]);
        return $this;
    }

    public function save()
    {
        $fname = FileSystem::tempfile(null, 0666);
        FileSystem::fputs($fname, $this);
        if($this->user)
            Shell::suexec("crontab $fname", $this->user, $this->pass);
        else
            Shell::exec("crontab $fname");
        FileSystem::rm($fname);
        return $this;
    }

    /**
     * @param $taskname
     * @return bool|TaskBase
     * @throws CrontabException
     */
    protected function taskClass($taskname)
    {
        $cname = TaskBase::getClass($taskname);
        if(!$cname)
            throw new CrontabException("Task not found: $taskname");
        return $cname;
    }

    /**
     * @param TaskBase $cname
     * @param array $args
     * @return bool
     */
    protected function getTaskIndex($cname, array $args = [])
    {
        $cmd = new CrontabTaskCommand($cname::getRunCommand($args));
        foreach($this->tasks as $index) {
            $task = $this->lines[$index];
            if($task->command->equals($cmd))
                return $index;
        }
        return false;
    }

}
