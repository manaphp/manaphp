<?php
declare(strict_types=1);

namespace App\Processes;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Swoole\Process\Attribute\Settings;
use ManaPHP\Swoole\ProcessInterface;
use Psr\Log\LoggerInterface;

#[Settings(2)]
class TestProcess implements ProcessInterface
{
    #[Autowired] protected LoggerInterface $logger;

    public function handle(): void
    {
        for (; ;) {
            $this->logger->error('abc');
            \sleep(1);
        }
    }
}