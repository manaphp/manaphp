<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;

class KeyCommand extends Command
{
    /**
     * generate random key
     *
     * @param int $length length of key(default is 32 characters)
     * @param int $lowercase
     */
    public function generateAction($length = 32, $lowercase = 0)
    {
        $key = $this->random->getBase($length);
        $this->console->writeLn($lowercase ? strtolower($key) : $key);
    }
}