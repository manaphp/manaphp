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
    public function defaultAction($name = 'manaphp')
    {
        $this->console->writeLn(['Hello %s!', $name]);
    }
}