<?php

namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

/**
 * ManaPHP\Mvc\View\Adapter\Php
 *
 * Adapter to use PHP itself as template engine
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
     *
     * @throws \ManaPHP\Renderer\Engine\Exception
     */
    public function render($file, $vars = [])
    {
        extract($vars, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        require $file;
    }
}