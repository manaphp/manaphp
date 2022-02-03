<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

class ViewContext
{
    public ?int $max_age;
    public null|false|string $layout = null;
    public array $vars = [];
    public string $content;
}
