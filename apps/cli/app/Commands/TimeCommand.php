<?php

namespace App\Commands;

use ManaPHP\Cli\Command;

/**
 * Class TimeCommand
 *
 * @package App\Commands
 *
 * @property-read \App\Services\TimeService $timeService
 */
class TimeCommand extends Command
{
    public function defaultAction()
    {
        $current = date('Y-m-d H:i:s', $this->timeService->current());
        var_dump($current);
    }
}