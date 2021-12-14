<?php
declare(strict_types=1);

namespace ManaPHP\Http;

class RouterContext
{
    public ?string $area = null;
    public ?string $controller = null;
    public ?string $action = null;
    public array $params = [];
    public bool $matched = false;
}