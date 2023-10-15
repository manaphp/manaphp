<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Helper\Arr;

class ConfigCommand extends Command
{
    #[Autowired] protected ConfigInterface $config;

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