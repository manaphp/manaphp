<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class KeyController extends Controller
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