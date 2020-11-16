<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;

/**
 * Class DotenvCommand
 *
 * @package App\Cli\Commands
 *
 * @property-read \ManaPHP\Configuration\DotenvInterface $dotenv
 */
class DotenvCommand extends Command
{
    public function defaultAction()
    {
        foreach ($this->dotenv->get() as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}
