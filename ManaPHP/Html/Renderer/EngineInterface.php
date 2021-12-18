<?php
declare(strict_types=1);

namespace ManaPHP\Html\Renderer;

interface EngineInterface
{
    public function render(string $file, array $vars = []): void;
}