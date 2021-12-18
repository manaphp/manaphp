<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface InvokerInterface
{
    public function invoke(Controller $controller, string $method): mixed;
}