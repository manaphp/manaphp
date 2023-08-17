<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

use ManaPHP\Eventing\EventArgs;

interface ValidatingFilterInterface
{
    public function onValidating(EventArgs $eventArgs): void;
}