<?php

namespace ManaPHP\Mvc\View;

interface WidgetInterface
{
    /**
     * @param array $vars
     *
     * @return string|array
     */
    public function run($vars = []);
}