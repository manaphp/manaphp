<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

class ViewContext
{
    public ?string $layout = null;
    public array $vars = [];
    public string $content;
}