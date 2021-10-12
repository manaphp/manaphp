<?php

namespace ManaPHP\Mvc\View\Flash\Adapter;

use ManaPHP\Mvc\View\AbstractFlash;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Session extends AbstractFlash
{
    /**
     * @var string
     */
    protected $key = 'manaphp_flash';

    /**
     * @param array $css
     */
    public function __construct($css = [])
    {
        parent::__construct($css);

        $context = $this->context;

        $context->messages = (array)$this->session->get($this->key, []);
        $this->session->remove($this->key);
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    protected function message($type, $message)
    {
        $css = $this->css[$type] ?? '';

        $messages = $this->session->get($this->key, []);
        $messages[] = '<div class="' . $css . '">' . $message . '</div>' . PHP_EOL;
        $this->session->set($this->key, $messages);
    }
}