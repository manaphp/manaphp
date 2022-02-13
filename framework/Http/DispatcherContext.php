<?php
declare(strict_types=1);

namespace ManaPHP\Http;

class DispatcherContext
{
    public ?string $path = null;
    public ?string $area = null;
    public ?string $controller = null;
    public ?string $action = null;
    public array $params = [];
    public ?Controller $controllerInstance = null;
    public bool $isInvoking = false;
}