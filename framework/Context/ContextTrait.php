<?php
declare(strict_types=1);

namespace ManaPHP\Context;

use ManaPHP\Di\Attribute\Inject;

trait ContextTrait
{
    #[Inject]
    protected ContextorInterface $contextor;
}