<?php

namespace ManaPHP\Commands;

/**
 * @property-read \ManaPHP\Configuration\DotenvInterface $dotenv
 */
class DotenvCommand extends \ManaPHP\Cli\Command
{
    /**
     * dump parsed .env values
     *
     * @return void
     */
    public function dumpAction()
    {
        foreach ($this->dotenv->get() as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}
