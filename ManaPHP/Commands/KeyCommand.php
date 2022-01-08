<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\Str;

class KeyCommand extends Command
{
    /**
     * generate random key
     *
     * @param int $length length of key(default is 32 characters)
     * @param int $lowercase
     *
     * @return void
     */
    public function generateAction(int $length = 32, int $lowercase = 0): void
    {
        $key = Str::random($length);
        $this->console->writeLn($lowercase ? strtolower($key) : $key);
    }
}