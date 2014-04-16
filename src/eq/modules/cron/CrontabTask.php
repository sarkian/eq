<?php
/**
 * Last Change: 2014 Apr 15, 20:37
 */

namespace eq\modules\cron;

class CrontabTask
{

    use \eq\base\TObject;

    protected $time;
    protected $command;

    public function __construct($line = null)
    {
        if(!$line)
            return;
        $line = trim($line, " \r\n\t");
        if(strncmp($line, "@", 1)) {
            $parts = preg_split("/[\s\t]+/", $line, 6);
            if(count($parts) !== 6)
                throw new CrontabException("Invalid line: $line");
            $command = array_pop($parts);
            $time = $parts;
        }
        else {
            $parts = preg_split("/[\s\t]+/", $line, 2);
            if(count($parts) !== 2)
                throw new CrontabException("Invalid line: $line");
            $command = array_pop($parts);
            $time = array_pop($parts);
        }
        $this->time = new CrontabTaskTime($time);
        $this->command = new CrontabTaskCommand($command);
    }

    public function __toString()
    {
        return $this->time." ".$this->command;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function setTime($time)
    {
        if(!$time instanceof CrontabTaskTime)
            $time = new CrontabTaskTime($time);
        $this->time = $time;
    }

    public function setCommand($command)
    {
        if(!$command instanceof CrontabTaskCommand)
            $command = new CrontabTaskCommand($command);
        $this->command = $command;
    }

}
