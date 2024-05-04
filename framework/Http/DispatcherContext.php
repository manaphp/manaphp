<?php
declare(strict_types=1);

namespace ManaPHP\Http;

class DispatcherContext
{
    public ?string $handler = null;
    public ?string $controller = null;
    public ?string $action = null;
    public array $params = [];
    public bool $isInvoking = false;
}