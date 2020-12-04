<?php

namespace ManaPHP\Cli\Commands;

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
    public function generateAction($length = 32, $lowercase = 0)
    {
        $key = Str::random($length);
        $this->console->writeLn($lowercase ? strtolower($key) : $key);
    }
}