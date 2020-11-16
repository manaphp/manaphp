<?php

namespace ManaPHP\Html\Renderer;

/**
 * Interface ManaPHP\Html\Renderer\EngineInterface
 *
 * @package renderer
 */
interface EngineInterface
{
    /**
     * Renders a view using the template engine
     *
     * @param string $file
     * @param array  $vars
     *
     * @return void
     */
    public function render($file, $vars = []);
}