<?php
namespace ManaPHP\Mvc\View\Flash\Adapter;

use ManaPHP\Mvc\View\Flash;

class Session extends Flash
{
    protected $_sessionKey = 'manaphp_flash';

    protected $_messages = [];

    /**
     * Session constructor.
     *
     * @param array $cssClasses
     */
    public function __construct($cssClasses = [])
    {
        parent::__construct($cssClasses);

        $defaultMessage = [];
        $this->_messages = $this->session->get($this->_sessionKey, $defaultMessage);
        $this->session->remove($this->_sessionKey);
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    public function _message($type, $message)
    {
        $cssClasses = isset($this->_cssClasses[$type]) ? $this->_cssClasses[$type] : '';

        $defaultMessages = [];
        $messages = $this->session->get($this->_sessionKey, $defaultMessages);
        $messages[] = '<div class="' . $cssClasses . '">' . $message . '</div>' . PHP_EOL;
        $this->session->set($this->_sessionKey, $messages);
    }

    /**
     * @param bool $remove
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