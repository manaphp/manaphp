<?php
declare(strict_types=1);

namespace App\Processes;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Swoole\AbstractProcess;
use Psr\Log\LoggerInterface;

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