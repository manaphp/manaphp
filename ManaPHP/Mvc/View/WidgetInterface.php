<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View;

interface WidgetInterface
{
    public function run(array $vars = []): string|array;
}