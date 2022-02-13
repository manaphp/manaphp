<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;

/**
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 */
class CryptCommand extends Command
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