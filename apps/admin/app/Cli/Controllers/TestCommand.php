<?php

namespace App\Cli\Controllers;

use ManaPHP\Cli\Command;

/**
 * Class TestCommand
 *
 * @package App\Cli\Commands
 */
class TestCommand extends Command
{
    public function defaultAction()
    {
        $this->console->writeLn(date('Y-m-d H:i:s'));
    }
}