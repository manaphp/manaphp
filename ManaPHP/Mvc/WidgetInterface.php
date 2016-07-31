<?php
namespace ManaPHP\Mvc;

interface WidgetInterface
{
    /**
     * @param array $vars
     *
     * @return string|array
     */
    public function run($vars = []);
}