<?php
/**
 * Last Change: 2014 Apr 15, 21:13
 */

namespace eq\modules\cron;

use EQ;
use eq\helpers\Shell;
use eq\helpers\FileSystem;
use eq\task\TaskBase;

class Crontab
{

    protected $lines = [];
    protected $tasks = [];

    public function __construct()
    {
        $this->reload();
    }

    public function reload()
    {
        $this->lines = [];
        $this->tasks = [];
        $out = Shell::exec("crontab -l");
        $lines = preg_split("/[\r\n]/", $out);
        foreach($lines as $i => $line) {
            $this->lines[$i] = $line;
            $line = trim($line, " \r\n\t");
            if(!$line)
                continue;
            if(!strncmp($line, "#", 1))
                continue;
            try {
                $task = new CrontabTask($line);
                $this->lines[$i] = $task;
                $this->tasks[] = $i;
            }
            catch(CrontabException $e) {}
        }
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

    public function addTask($time, $taskname, array $args = [])
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
    }

    public function removeTask($taskname, array $args = [])
    {
        $cname = $this->taskClass($taskname);
        $cmd = new CrontabTaskCommand($cname::getRunCommand());
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
    }

    public function save()
    {
        $fname = EQ::getAlias("@runtime/crontab.tmp");
        FileSystem::fputs($fname, $this);
        Shell::exec("crontab $fname");
    }

    protected function taskClass($taskname)
    {
        $cname = TaskBase::getClass($taskname);
        if(!$cname)
            throw new CrontabException("Cant find task: $taskname");
        return $cname;
    }

    protected function getTaskIndex($cname, array $args = [])
    {
        $cmd = new CrontabTaskCommand($cname::getRunCommand());
        foreach($this->tasks as $index) {
            $task = $this->lines[$index];
            if($task->command->equals($cmd))
                return $index;
        }
        return false;
    }

}
