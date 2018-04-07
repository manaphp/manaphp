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
     * @var string[]
     */
    protected $_messages = [];

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
        $cssClasses = isset($this->_cssClasses[$type]) ? $this->_cssClasses[$type] : '';

        $this->_messages[] = '<div class="' . $cssClasses . '">' . $message . '</div>' . PHP_EOL;
    }

    /**
     * Prints the messages in the session flasher
     *
     * @param $remove bool
     *
     * @return void
     */
    public function _output($remove = true)
    {
        foreach ($this->_messages as $message) {
            echo $message;
        }

        if ($remove) {
            $this->_messages = [];
        }
    }
}