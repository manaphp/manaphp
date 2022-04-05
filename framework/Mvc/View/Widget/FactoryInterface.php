<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Widget;

use ManaPHP\Mvc\View\WidgetInterface;

interface FactoryInterface
{
    public function get(string $widget): WidgetInterface;
}