<?php
declare(strict_types=1);

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
        $this->console->debug('hello {name}!', ['name' => $name]);
    }
}
