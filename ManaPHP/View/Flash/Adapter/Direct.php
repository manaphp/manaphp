<?php

namespace ManaPHP\View\Flash\Adapter;

use ManaPHP\View\Flash;

/**
 * Class ManaPHP\View\Flash\Adapter\Direct
 *
 * @package flash\adapter
 */
class Direct extends Flash
{
    /**
     * Outputs a message
     *
     * @param  string $type
     * @param  string $message
     *
     * @return void
     */
    public function _message($type, $message)
    {
        $context = $this->_context;

        $cssClasses = isset($this->_cssClasses[$type]) ? $this->_cssClasses[$type] : '';

        $context->messages[] = '<div class="' . $cssClasses . '">' . $message . '</div>' . PHP_EOL;
    }
}