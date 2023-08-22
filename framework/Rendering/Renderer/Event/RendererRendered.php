<?php
declare(strict_types=1);

namespace ManaPHP\Rendering\Renderer\Event;

use ManaPHP\Rendering\RendererInterface;

class RendererRendered
{
    public function __construct(
        public RendererInterface $renderer,
        public string $template,
        public string $file,
        public array $vars,
    ) {

    }
}