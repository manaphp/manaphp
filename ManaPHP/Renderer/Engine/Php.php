<?php

namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Php
 *
 * @package renderer\engine
 */
class Php extends Component implements EngineInterface
{
    /**
     * Renders a view using the template engine
     *
     * @param string $file
     * @param array  $vars
     *
     * @return void
     */
    public function render($file, $vars = [])
    {
        extract($vars, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        require $file;
    }
}