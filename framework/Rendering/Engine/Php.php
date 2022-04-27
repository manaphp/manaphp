<?php
declare(strict_types=1);

namespace ManaPHP\Rendering\Engine;

use ManaPHP\Component;
use ManaPHP\Rendering\EngineInterface;

class Php extends Component implements EngineInterface
{
    public function render(string $file, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);

        require $file;
    }
}