<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class SchedulerMaker implements SchedulerMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters = []): mixed
    {
        return $this->maker->make(Scheduler::class, $parameters);
    }
}