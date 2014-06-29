<?php

namespace eq\modules\cron;

use eq\helpers\Shell;
use eq\base\InvalidArgumentException;

/**
 * @property array args
 * @property string comment
 * @property bool origin
 */
class CrontabTaskCommand
{

    protected $args = [];
    protected $comment = "";
    protected $origin = false;

    public function __construct($command = "", $origin = false)
    {
        if($command) {
            if(is_string($command))
                $this->args = Shell::split($command, false, $this->comment);
            elseif(is_array($command))
                $this->args = $command;
            else
                throw new InvalidArgumentException("Invalid argument type: ".gettype($command));
            $this->origin = $origin;
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

    public function getOrigin()
    {
        return $this->origin;
    }

    public function __toString()
    {
        return Shell::join($this->args, $this->comment);
    }

    public function equals($cmd)
    {
        $cmd instanceof CrontabTaskCommand or $cmd = new CrontabTaskCommand($cmd);
        if(($this->origin && $cmd->origin) || (!$this->origin && !$cmd->origin))
            return $cmd->args === $this->args;
        $len = count($this->origin ? $this->args : $cmd->args);
        return array_slice($this->args, 0, $len) === array_slice($cmd->args, 0, $len);
    }

}
