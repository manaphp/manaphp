<?php

namespace ManaPHP\Configuration;

use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 */
class Command extends \ManaPHP\Cli\Command
{
    /**
     * @param string $path
     */
    public function dumpAction($path = '')
    {
        $config = $this->configure->getConfig();
        $config = Arr::get($config, $path);

        $this->console->writeLn(json_stringify($config, JSON_PRETTY_PRINT));
    }
}