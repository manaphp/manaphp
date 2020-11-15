<?php

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\Mailer;

class File extends Mailer
{
    /**
     * @var string
     */
    protected $_file;

    /**
     * @var bool
     */
    protected $_pretty = false;

    /**
     * File constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['file'])) {
            $this->_file = $options['file'];
        }
        if (isset($options['pretty'])) {
            $this->_pretty = (bool)$options['pretty'];
        }

        if (isset($options['log'])) {
            $this->_log = $options['log'];
        }

        if (isset($options['from'])) {
            $this->_from = $options['from'];
        }

        if (isset($options['to'])) {
            $this->_to = $options['to'];
        }
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    protected function _send($message, &$failedRecipients = null)
    {
        if ($this->_pretty) {
            $data = str_repeat('=', 20) . date('Y-m-d H:i:s') . str_repeat('=', 20)
                . PHP_EOL
                . json_stringify($message, JSON_PRETTY_PRINT)
                . PHP_EOL;
        } else {
            $data = json_stringify($message) . PHP_EOL;
        }

        LocalFS::fileAppend($this->_file ?: '@data/fileMailer/mailer_' . date('ymd') . '.log', $data);

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}