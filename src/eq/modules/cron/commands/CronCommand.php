<?php

namespace eq\modules\cron\commands;

use eq\console\Command;
use eq\modules\cron\Crontab;
use eq\modules\cron\CrontabTaskCommand;
use eq\modules\cron\CrontabTaskTime;
use eq\helpers\Shell;

/**
 * Base cron command
 */
class CronCommand extends Command
{

    public function actionTest()
    {
        
    }

    /**
     * Add task into crontab
     *
     * @param string $taskname Task (not task class) name
     */
    public function actionAddTask($taskname)
    {
        
    }

}
