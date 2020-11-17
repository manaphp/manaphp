<?php

namespace ManaPHP\Configuration\Dotenv;

/**
 * Class DotenvCommand
 *
 * @package App\Cli\Commands
 *
 * @property-read \ManaPHP\Configuration\DotenvInterface $dotenv
 */
class Command extends \ManaPHP\Cli\Command
{
    public function defaultAction()
    {
        foreach ($this->dotenv->get() as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}
