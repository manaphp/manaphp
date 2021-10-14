<?php

namespace ManaPHP\Commands;

use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class ConfigCommand extends \ManaPHP\Cli\Command
{
    /**
     * dump of the app.php
     *
     * @param string $path
     */
    public function dumpAction($path = '')
    {
        $config = $this->config->get();
        $config = Arr::get($config, $path);

        $this->console->writeLn(json_stringify($config, JSON_PRETTY_PRINT));
    }
}