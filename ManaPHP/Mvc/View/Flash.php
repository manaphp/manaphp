<?php

namespace ManaPHP\Mvc\View;

use ManaPHP\Component;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class FlashContext
{
    /**
     * @var string[]
     */
    public $messages = [];
}

/**
 * @property-read \ManaPHP\Mvc\View\FlashContext $_context
 */
abstract class Flash extends Component implements FlashInterface
{
    /**
     * @var array
     */
    protected $_css;

    /**
     * @param array $css
     */
    public function __construct($css = [])
    {
        $this->_css = $css
            ?: [
                'error'   => 'flash-error',
                'notice'  => 'flash-notice',
                'success' => 'flash-success',
                'warning' => 'flash-warning'
            ];
    }

    /**
     * Outputs a message
     *
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    abstract protected function _message($type, $message);

    /**
     * Shows a HTML error message
     *
     * @param string $message
     *
     * @return void
     */
    public function error($message)
    {
        $this->_message('error', $message);
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
        $this->_message('notice', $message);
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
        $this->_message('notice', $message);
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
        $this->_message('warning', $message);
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
        $context = $this->_context;

        foreach ($context->messages as $message) {
            echo $message;
        }

        if ($remove) {
            $context->messages = [];
        }
    }
}