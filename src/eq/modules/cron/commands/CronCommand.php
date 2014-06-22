<?php

namespace eq\modules\cron\commands;

use EQ;
use eq\console\Command;
use eq\helpers\C;
use eq\helpers\FileSystem;
use eq\modules\cron\Crontab;
use eq\modules\cron\CrontabException;

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
     * @throws \eq\base\FileSystemException
     * @return int
     */
    public function actionAddTask($taskname, $time = "*/15 * * * *", $args = [])
    {
        try {
            $crontab = new Crontab();
            $crontab->addTask($taskname, $time, $args);
            $crontab->save();
            FileSystem::fputs("@runtime/config.s", serialize(EQ::app()->config()));
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
