<?php
declare(strict_types=1);

namespace ManaPHP\Html\Renderer\Engine;

use ManaPHP\Html\Renderer\EngineInterface;

interface FactoryInterface
{
    public function get(string $engine): EngineInterface;
}