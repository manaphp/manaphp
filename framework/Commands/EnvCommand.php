<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\EnvInterface;

class EnvCommand extends Command
{
    #[Inject]
    protected EnvInterface $env;

    /**
     * dump parsed .env values
     *
     * @return void
     */
    public function dumpAction(): void
    {
        foreach ($this->env->all() as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}
