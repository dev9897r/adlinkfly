<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cron\CronExpression;

class ScheduleCommand extends Command
{
    /**
     * php bin/cake.php schedule
     * bin/cake schedule
     *
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return int|void|null
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        // https://github.com/dragonmantank/cron-expression#usage
        if (CronExpression::factory('* * * * *')->isDue()) {
            $io->out('* * * * *');
        }

        if (CronExpression::factory('*/5 * * * *')->isDue()) {
            $io->out('*/5 * * * *');
        }
    }
}
