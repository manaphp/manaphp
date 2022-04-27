<?php
declare(strict_types=1);

namespace ManaPHP\Rendering;

interface EngineInterface
{
    public function render(string $file, array $vars = []): void;
}