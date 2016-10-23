<?php

namespace ManaPHP\Mvc\View\Flash\Adapter;

use ManaPHP\Mvc\View\Flash;

/**
 * Class ManaPHP\Mvc\View\Flash\Adapter\Direct
 *
 * @package ManaPHP\Mvc\View\Flash\Adapter
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