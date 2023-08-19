<?php
declare(strict_types=1);

namespace ManaPHP\Http\Action;

interface InvokerInterface
{
    public function invoke(object $object, string $action): mixed;
}