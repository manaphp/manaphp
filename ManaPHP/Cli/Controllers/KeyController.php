<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class KeyController extends Controller
{
    /**
     * @CliCommand generate random key
     * @CliParam   --length,-l length of key(default is 32 characters)
     * @CliParam   --lowercase
     */
    public function generateCommand()
    {
        $length = $this->arguments->getOption('length:l', 32);

        $key = $this->random->getBase($length);

        if ($this->arguments->hasOption('lowercase')) {
            $key = strtolower($key);
        }

        $this->console->writeLn($key);
    }
}