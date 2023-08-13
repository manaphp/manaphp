<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Security\CryptInterface;

class CryptCommand extends Command
{
    #[Inject]
    protected CryptInterface $crypt;

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