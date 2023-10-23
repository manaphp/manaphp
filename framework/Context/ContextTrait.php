<?php
declare(strict_types=1);

namespace ManaPHP\Context;

use ManaPHP\Di\Attribute\Autowired;

trait ContextTrait
{
    #[Autowired] protected ContextorInterface $contextor;

    protected function getContext(int $cid = 0): object
    {
        return $this->contextor->getContext($this, $cid);
    }
}