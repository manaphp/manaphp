<?php

namespace ManaPHP\Html\Renderer;

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