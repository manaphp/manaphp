<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\ViewInterface
 *
 * @package view
 */
interface ViewInterface
{
    /**
     * @param false|string $layout
     *
     * @return static
     */

    public function setLayout($layout = 'Default');

    /**
     * Adds parameter to view
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setVar($name, $value);

    /**
     * Adds parameters to view
     *
     * @param array $vars
     *
     * @return static
     */
    public function setVars($vars);

    /**
     * Returns a parameter previously set in the view
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getVar($name = null);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasVar($name);

    /**
     * Executes render process from dispatching data
     *
     * @param string $template
     */
    public function render($template = null);

    /**
     * Renders a partial view
     *
     * <code>
     *    //Show a partial inside another view
     *    $this->partial('shared/footer');
     * </code>
     *
     * <code>
     *    //Show a partial inside another view with parameters
     *    $this->partial('shared/footer', array('content' => $html));
     * </code>
     *
     * @param string $path
     * @param array  $vars
     */
    public function partial($path, $vars = []);

    /**
     * Renders a widget
     *
     * @param string    $widget
     * @param array     $vars
     * @param int|array $cacheOptions
     */
    public function widget($widget, $vars = [], $cacheOptions = null);

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function block($path, $vars = []);

    /**
     * Externally sets the view content
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content);

    /**
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent();
}