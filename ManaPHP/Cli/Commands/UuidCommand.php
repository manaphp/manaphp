<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\Str;

class UuidCommand extends Command
{
    /**
     * generate uuid
     *
     * @param int $count
     */
    public function defaultAction($count = 5)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->console->writeLn(Str::uuid());
        }
    }
}