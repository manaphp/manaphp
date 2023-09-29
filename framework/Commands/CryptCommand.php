<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Security\CryptInterface;

class CryptCommand extends Command
{
    #[Autowired] protected CryptInterface $crypt;

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