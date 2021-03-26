<?php

namespace App\Commands;

use App\Services\TimeService;

/**
 * Class TimeCommand
 *
 * @package App\Commands
 * @property-read TimeService $timeService
 */
class TimeCommand extends Command
{
    /**
     * current time
     */
    public function currentAction()
    {
        $this->console->writeLn(date('Y-m-d H:i:s', $this->timeService->current()));
    }

    /**
     * after some seconds
     *
     * @param int $second
     */
    public function afterAction($second = 30)
    {
        $this->console->writeLn(date('Y-m-d H:i:s', $this->timeService->after($second)));
    }
}