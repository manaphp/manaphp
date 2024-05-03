<?php
declare(strict_types=1);

namespace ManaPHP\Http;

class DispatcherContext
{
    public ?string $handler = null;
    public ?string $controller = null;
    public ?string $action = null;
    public array $params = [];
    public ?object $controllerInstance = null;
    public bool $isInvoking = false;
}