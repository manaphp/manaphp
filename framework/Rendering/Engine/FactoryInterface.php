<?php
declare(strict_types=1);

namespace ManaPHP\Rendering\Engine;

use ManaPHP\Rendering\EngineInterface;

interface FactoryInterface
{
    public function get(string $engine): EngineInterface;
}