<?php
declare(strict_types=1);

namespace ManaPHP\Html\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Html\Renderer\EngineInterface;

class Php extends Component implements EngineInterface
{
    public function render(string $file, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);

        require $file;
    }
}