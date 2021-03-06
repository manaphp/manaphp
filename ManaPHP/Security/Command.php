<?php

namespace ManaPHP\Security;

/**
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 */
class Command extends \ManaPHP\Cli\Command
{
    /**
     * @param string $type
     */
    public function derivedAction($type)
    {
        $this->console->writeLn($this->crypt->getDerivedKey($type));
    }
}