<?php
declare(strict_types=1);

namespace ManaPHP\Http;

class DispatcherContext
{
    public ?string $path = null;
    public ?string $area = null;
    public string $controller;
    public string $action;
    public array $params = [];
    public Controller $controllerInstance;
    public bool $isInvoking = false;
}