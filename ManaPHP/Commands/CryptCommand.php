<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

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
    public function derivedAction(string $type): void
    {
        $this->console->writeLn($this->crypt->getDerivedKey($type));
    }
}