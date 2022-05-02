<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;

/**
 * @property-read \ManaPHP\EnvInterface $env
 */
class EnvCommand extends Command
{
    /**
     * dump parsed .env values
     *
     * @return void
     */
    public function dumpAction(): void
    {
        foreach ($this->env->get(null) as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}
