<?php

namespace ManaPHP\Mvc;

interface ViewInterface
{
    /**
     * @param int $max_age
     *
     * @return static
     */
    public function setMaxAge($max_age);

    /**
     * @return int
     */
    public function getMaxAge();

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
     * @param string $template
     *
     * @return bool
     */
    public function exists($template = null);

    /**
     * Executes render process from dispatching data
     *
     * @param string $template
     * @param array  $vars
     *
     * @return  string
     */
    public function render($template = null, $vars = []);

    /**
     * Renders a widget
     *
     * @param string $widget
     * @param array  $vars
     *
     * @return void
     */
    public function widget($widget, $vars = []);

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