<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

use ManaPHP\Eventing\EventArgs;

interface ReadyFilterInterface
{
    public function onReady(EventArgs $eventArgs): void;
}