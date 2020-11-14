<?php

namespace ManaPHP\Mvc\View\Flash\Adapter;

use ManaPHP\Mvc\View\Flash;

/**
 * Class ManaPHP\Mvc\View\Flash\Adapter\Direct
 *
 * @package flash\adapter
 */
class Direct extends Flash
{
    /**
     * Outputs a message
     *
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    protected function _message($type, $message)
    {
        $context = $this->_context;

        $css = $this->_css[$type] ?? '';

        $context->messages[] = '<div class="' . $css . '">' . $message . '</div>' . PHP_EOL;
    }
}