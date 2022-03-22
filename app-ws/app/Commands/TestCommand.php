<?php

namespace App\Commands;

class TestCommand extends Command
{
    /**
     * demo for you
     *
     * @param string $name your name
     */
    public function defaultAction($name = 'manaphp')
    {
        $this->console->debug(sprintf('hello %s!', $name));
    }
}