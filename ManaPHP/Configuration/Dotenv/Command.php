<?php

namespace ManaPHP\Configuration\Dotenv;

/**
 * @property-read \ManaPHP\Configuration\DotenvInterface $dotenv
 */
class Command extends \ManaPHP\Cli\Command
{
    /**
     * dump parsed .env values
     *
     * @return void
     */
    public function defaultAction()
    {
        foreach ($this->dotenv->get() as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}
