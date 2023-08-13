<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Version;

class VersionCommand extends Command
{
    #[Inject] protected ConfigInterface $config;

    public function showAction()
    {
        $this->console->writeLn('      php: ' . PHP_VERSION);
        $this->console->writeLn('   swoole: ' . (defined('SWOOLE_VERSION') ? SWOOLE_VERSION : 'n/a'));
        $this->console->writeLn('framework: ' . Version::get());
    }
}