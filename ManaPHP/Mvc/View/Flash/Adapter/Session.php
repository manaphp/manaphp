<?php

namespace ManaPHP\Mvc\View\Flash\Adapter;

use ManaPHP\Mvc\View\Flash;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Session extends Flash
{
    /**
     * @var string
     */
    protected $_key = 'manaphp_flash';

    /**
     * @param array $css
     */
    public function __construct($css = [])
    {
        parent::__construct($css);

        $context = $this->_context;

        $context->messages = (array)$this->session->get($this->_key, []);
        $this->session->remove($this->_key);
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    protected function message($type, $message)
    {
        $css = $this->_css[$type] ?? '';

        $messages = $this->session->get($this->_key, []);
        $messages[] = '<div class="' . $css . '">' . $message . '</div>' . PHP_EOL;
        $this->session->set($this->_key, $messages);
    }
}