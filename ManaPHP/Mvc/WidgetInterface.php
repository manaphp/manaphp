<?php
namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\WidgetInterface
 *
 * @package ManaPHP\Mvc
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