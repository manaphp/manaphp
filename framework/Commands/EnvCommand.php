<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\EnvInterface;

class EnvCommand extends Command
{
    #[Autowired] protected EnvInterface $env;

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
