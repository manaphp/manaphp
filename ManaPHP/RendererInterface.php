<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\RendererInterface
 *
 * @package renderer
 */
interface RendererInterface
{
    /**
     * Checks whether view exists on registered extensions and render it
     *
     * @param string $template
     * @param array  $vars
     * @param bool   $directOutput
     *
     * @return string
     */
    public function render($template, $vars = [], $directOutput = false);

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function partial($path, $vars = []);

    /**
     * @param string $template
     *
     * @return bool
     */
    public function exists($template);

    /**
     * Get the string contents of a section.
     *
     * @param string $section
     * @param string $default
     *
     * @return string
     */
    public function getSection($section, $default = '');

    /**
     * Start injecting content into a section.
     *
     * @param string $section
     * @param string $default
     *
     * @return void
     */
    public function startSection($section, $default = null);

    /**
     * Stop injecting content into a section.
     *
     * @param bool $overwrite
     *
     * @return void
     */
    public function stopSection($overwrite = false);

    /**
     * @return void
     */
    public function appendSection();
}