<?php
declare(strict_types=1);

namespace ManaPHP\Context;

use ManaPHP\Di\Attribute\Autowired;

trait ContextTrait
{
    #[Autowired] protected ContextManagerInterface $contextManager;

    protected function getContext(int $cid = 0): mixed
    {
        return $this->contextManager->getContext($this, $cid);
    }
}