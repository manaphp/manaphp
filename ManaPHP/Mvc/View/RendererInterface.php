<?php
namespace ManaPHP\Mvc\View;

interface RendererInterface
{
    /**
     * Checks whether view exists on registered extensions and render it
     *
     * @param string  $template
     * @param boolean $directOutput
     * @param array   $vars
     *
     * @return string
     * @throws \ManaPHP\Mvc\View\Exception
     */
    public function render($template, $vars, $directOutput = true);

    /**
     * @param string $template
     *
     * @return bool
     */
    public function exists($template);
}