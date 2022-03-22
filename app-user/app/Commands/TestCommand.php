<?php

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
        $this->console->writeLn(sprintf('Hello %s!', $name));
    }
}