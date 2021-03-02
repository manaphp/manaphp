<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Version;

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 */
class VersionCommand extends Command
{
    public function defaultAction()
    {
        $this->console->writeLn('      php: ' . PHP_VERSION);
        $this->console->writeLn('   swoole: ' . (defined('SWOOLE_VERSION') ? SWOOLE_VERSION : 'n/a'));
        $this->console->writeLn('      app: ' . $this->configure->version);
        $this->console->writeLn('framework: ' . Version::get());
    }
}