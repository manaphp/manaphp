<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class ConfigCommand extends Command
{
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