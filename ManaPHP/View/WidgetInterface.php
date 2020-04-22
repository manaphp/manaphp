<?php

namespace ManaPHP\View;

/**
 * Interface ManaPHP\View\WidgetInterface
 *
 * @package view
 */
interface WidgetInterface
{
    /**
     * @param array $vars
     *
     * @return string|array
     */
    public function run($vars = []);
}