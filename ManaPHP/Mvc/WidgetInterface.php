<?php
namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\WidgetInterface
 *
 * @package widget
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