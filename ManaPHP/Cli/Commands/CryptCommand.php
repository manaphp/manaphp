<?php

namespace ManaPHP\Cli\Commands;

/**
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 */
class CryptCommand extends \ManaPHP\Cli\Command
{
    /**
     * get the derived key
     *
     * @param string $type
     */
    public function derivedAction($type)
    {
        $this->console->writeLn($this->crypt->getDerivedKey($type));
    }
}