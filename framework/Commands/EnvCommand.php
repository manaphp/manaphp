<?php

namespace ManaPHP\Commands;

/**
 * @property-read \ManaPHP\EnvInterface $env
 */
class EnvCommand extends \ManaPHP\Cli\Command
{
    /**
     * dump parsed .env values
     *
     * @return void
     */
    public function dumpAction()
    {
        foreach ($this->env->get(null) as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}
