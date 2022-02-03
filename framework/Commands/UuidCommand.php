<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\Str;

class UuidCommand extends Command
{
    /**
     * generate uuid
     *
     * @param int $count
     */
    public function defaultAction(int $count = 5): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->console->writeLn(Str::uuid());
        }
    }
}