<?php

namespace eq\modules\cron;

use eq\helpers\Shell;
use eq\base\InvalidArgumentException;

class CrontabTaskCommand
{

    protected $args = [];
    protected $comment = "";

    public function __construct($command = "", $comment = "")
    {
        if($command) {
            if(is_string($command))
                $this->args = Shell::split($command, false, $this->comment);
            elseif(is_array($command))
                $this->args = $command;
            else
                throw new InvalidArgumentException("Invalid argument type: ".gettype($command));
            $comment = trim($comment, " \r\n\t");
            $comment = preg_replace("/^#/", "", $comment);
            $comment = ltrim($comment, " \r\n\t");
            if($comment)
                $this->comment = $comment;
        }
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function __toString()
    {
        $str = implode(" ", array_map(['eq\helpers\Shell', "escapeArg"], $this->args));
        if($this->comment)
            $str .= " # ".$this->comment;
        return $str;
    }

    public function equals($cmd)
    {
        $cmd instanceof CrontabTaskCommand or $cmd = new CrontabTaskCommand($cmd);
        return $cmd->args === $this->args;
    }

}
