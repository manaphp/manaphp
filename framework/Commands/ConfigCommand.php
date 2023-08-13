<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Helper\Arr;

class ConfigCommand extends Command
{
    #[Inject] protected ConfigInterface $config;

    /**
     * dump of the app.php
     *
     * @param string $path
     */
    public function dumpAction(string $path = ''): void
    {
        $config = $this->config->all();
        $config = Arr::get($config, $path);

        $this->console->writeLn(json_stringify($config, JSON_PRETTY_PRINT));
    }
}