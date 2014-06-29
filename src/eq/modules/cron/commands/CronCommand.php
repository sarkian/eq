<?php

namespace eq\modules\cron\commands;

use EQ;
use eq\console\Command;
use eq\console\CommandException;
use eq\helpers\C;
use eq\helpers\FileSystem;
use eq\modules\cron\Crontab;
use eq\modules\cron\CrontabException;
use eq\task\TaskBase;

/**
 * Base cron command
 */
class CronCommand extends Command
{

    /**
     * Add task into crontab
     *
     * @param string $taskname Task (not task class) name
     * @param string $time Time in crontab format
     * @param array $args, ... Task arguments
     * @option string $out  STDOUT file
     * @option string $err  STDERR file
     * @option string $run  Run mode. Default: %conce%0. Supported values:
     *                      %conce%0 - Exit, if already running with same arguments
     *                      %cforce%0 - Run anyway
     *                      %crestart%0 - Kill, if already running with same arguments
     *                      %cqueue%0 - Add to queue
     * @option bool   $append-out Append STDOUT to outlog
     * @option bool   $append-err Append STDERR to errlog
     * @throws \eq\base\FileSystemException
     * @return int
     */
    public function actionAddTask($taskname, $time = "*/15 * * * *", $args = [])
    {
        $modes = [
            'once' => TaskBase::R_ONCE,
            'force' => TaskBase::R_FORCE,
            'restart' => TaskBase::R_RESTART,
            'queue' => TaskBase::R_QUEUE,
        ];
        $run = EQ::app()->action_options['run'];
        $run !== null or $run = "once";
        if(!isset($modes[$run])) {
            C::renderErr("%r%1Invalid run mode: %$%0\n"
                ."Supported modes: %conce%0, %cforce%0, %crestart%0, %cqueue%0", $run);
            return -1;
        }
        $run = $modes[$run];
        $options = [
            'outlog' => EQ::app()->action_options['out'],
            'errlog' => EQ::app()->action_options['err'],
            'append_outlog' => EQ::app()->action_options['append-out'],
            'append_errlog' => EQ::app()->action_options['append-err'],
        ];
        try {
            $crontab = new Crontab();
            $crontab->addTask($taskname, $time, $args, $run, $options);
            $crontab->save();
        }
        catch(CrontabException $e) {
            C::renderErr("%r%1%$%0", $e->getMessage());
            return -1;
        }
        return 0;
    }

    /**
     * Remove task from crontab
     *
     * @param string $taskname Task (not task class) name
     * @param array $args, ... Task arguments
     * @return int
     */
    public function actionRemoveTask($taskname, $args = [])
    {
        try {
            $crontab = new Crontab();
            $crontab->removeTask($taskname, $args);
            $crontab->save();
        }
        catch(CrontabException $e) {
            C::renderErr("%r%1%$%0", $e->getMessage());
            return -1;
        }
        return 0;
    }

}
