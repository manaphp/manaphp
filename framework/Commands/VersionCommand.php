<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Version;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class VersionCommand extends Command
{
    public function showAction()
    {
        $this->console->writeLn('      php: ' . PHP_VERSION);
        $this->console->writeLn('   swoole: ' . (defined('SWOOLE_VERSION') ? SWOOLE_VERSION : 'n/a'));
        $this->console->writeLn('      app: ' . $this->config->get('version'));
        $this->console->writeLn('framework: ' . Version::get());
    }
}