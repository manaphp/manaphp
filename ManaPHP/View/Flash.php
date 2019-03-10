<?php

namespace ManaPHP\View;

use ManaPHP\Component;
use ManaPHP\View\Flash\AdapterInterface;

class FlashContext
{
    /**
     * @var string[]
     */
    public $messages = [];
}

/**
 * Class ManaPHP\View\Flash
 *
 * @package flash
 * @property \ManaPHP\View\FlashContext $_context
 */
abstract class Flash extends Component implements FlashInterface, AdapterInterface
{
    /**
     * @var array
     */
    protected $_cssClasses;

    /**
     * \ManaPHP\Flash constructor
     *
     * @param array $cssClasses
     */
    public function __construct($cssClasses = [])
    {
        $this->_context = new FlashContext();

        $this->_cssClasses = $cssClasses ?: [
            'error' => 'flash-error',
            'notice' => 'flash-notice',
            'success' => 'flash-success',
            'warning' => 'flash-warning'
        ];
    }

    /**
     * Shows a HTML error message
     *
     *<code>
     * $flash->error('This is an error');
     *</code>
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
     *<code>
     * $flash->notice('This is an information');
     *</code>
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
     *<code>
     * $flash->success('The process was finished successfully');
     *</code>
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
     *<code>
     * $flash->warning('Hey, this is important');
     *</code>
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