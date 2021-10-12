<?php

namespace ManaPHP\Mvc\View\Flash\Adapter;

use ManaPHP\Mvc\View\AbstractFlash;

class Direct extends AbstractFlash
{
    /**
     * Outputs a message
     *
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    protected function message($type, $message)
    {
        $context = $this->context;

        $css = $this->css[$type] ?? '';

        $context->messages[] = '<div class="' . $css . '">' . $message . '</div>' . PHP_EOL;
    }
}