<?php
declare(strict_types=1);

namespace App\Processes;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Swoole\AbstractProcess;
use ManaPHP\Swoole\Process\Attribute\Settings;
use Psr\Log\LoggerInterface;

#[Settings(10)]
class TestProcess extends AbstractProcess
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