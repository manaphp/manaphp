<?php

namespace ManaPHP\Mvc\View;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Mvc\View\FlashContext $context
 */
class Flash extends Component implements FlashInterface
{
    /**
     * @var array
     */
    protected $css;

    /**
     * @param array $css
     */
    public function __construct($css = [])
    {
        $this->css = $css
            ?: [
                'error'   => 'flash-error',
                'notice'  => 'flash-notice',
                'success' => 'flash-success',
                'warning' => 'flash-warning'
            ];
    }

    /**
     * Shows a HTML error message
     *
     * @param string $message
     *
     * @return void
     */
    public function error($message)
    {
        $this->message('error', $message);
    }

    /**
     * Shows a HTML notice/information message
     *
     * @param string $message
     *
     * @return void
     */
    public function notice($message)
    {
        $this->message('notice', $message);
    }

    /**
     * Shows a HTML success message
     *
     * @param string $message
     *
     * @return void
     */
    public function success($message)
    {
        $this->message('notice', $message);
    }

    /**
     * Shows a HTML warning message
     *
     * @param string $message
     *
     * @return void
     */
    public function warning($message)
    {
        $this->message('warning', $message);
    }

    /**
     * Prints the messages in the session flasher
     *
     * @param bool $remove
     *
     * @return void
     */
    public function output($remove = true)
    {
        $context = $this->context;

        foreach ($context->messages as $message) {
            echo $message;
        }

        if ($remove) {
            $context->messages = [];
        }
    }

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