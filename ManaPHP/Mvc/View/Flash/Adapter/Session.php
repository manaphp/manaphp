<?php
namespace ManaPHP\Mvc\View\Flash\Adapter;

use ManaPHP\Mvc\View\Flash;

class Session extends Flash
{
    protected $_sessionKey = 'manaphp_flash';

    protected $_messages = [];

    public function __construct($cssClasses = null)
    {
        parent::__construct($cssClasses);

        $this->_messages = $this->session->get($this->_sessionKey, []);
        $this->session->remove($this->_sessionKey);
    }

    public function _message($type, $message)
    {
        $cssClasses = isset($this->_cssClasses[$type]) ? $this->_cssClasses[$type] : '';

        $this->session->set($this->_sessionKey,
            array_merge($this->session->get($this->_sessionKey, []),
                ['<div class="' . $cssClasses . '">' . $message . '</div>' . PHP_EOL]));
    }

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