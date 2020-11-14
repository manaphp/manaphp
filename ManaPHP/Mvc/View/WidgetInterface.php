<?php

namespace ManaPHP\Mvc\View;

/**
 * Interface ManaPHP\Mvc\View\WidgetInterface
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