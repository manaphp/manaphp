<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 */
class ConfigCommand extends Command
{
    /**
     * @param string $path
     */
    public function defaultAction($path = '')
    {
        $config = $this->configure->getConfig();
        $config = Arr::get($config, $path);

        $this->console->writeLn(json_stringify($config, JSON_PRETTY_PRINT));
    }
}