<?php
declare(strict_types=1);

namespace App\Commands;

class TestCommand extends Command
{
    /**
     * demo how to use command
     *
     * @param string $name
     */
    public function defaultAction(string $name = 'manaphp')
    {
        $this->console->writeLn(sprintf('Hello %s!', $name));
    }
}