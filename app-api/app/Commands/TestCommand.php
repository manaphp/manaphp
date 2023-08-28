<?php
declare(strict_types=1);

namespace App\Commands;

use ManaPHP\Exception;

class TestCommand extends Command
{
    /**
     * demo how to use command
     *
     * @param string $name
     */
    public function defaultAction(string $name = 'manaphp'): void
    {
        $e = new Exception(['abc is {3}','xx']);
        $this->console->writeLn($e->getMessage());
        $this->console->writeLn(sprintf('Hello %s!', $name));
    }
}