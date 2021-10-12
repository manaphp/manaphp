<?php

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\AbstractMailer;

class File extends AbstractMailer
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var bool
     */
    protected $pretty = false;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['file'])) {
            $this->file = $options['file'];
        }
        if (isset($options['pretty'])) {
            $this->pretty = (bool)$options['pretty'];
        }

        if (isset($options['log'])) {
            $this->log = $options['log'];
        }

        if (isset($options['from'])) {
            $this->from = $options['from'];
        }

        if (isset($options['to'])) {
            $this->to = $options['to'];
        }
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    protected function sendInternal($message, &$failedRecipients = null)
    {
        if ($this->pretty) {
            $data = str_repeat('=', 20) . date('Y-m-d H:i:s') . str_repeat('=', 20)
                . PHP_EOL
                . json_stringify($message, JSON_PRETTY_PRINT)
                . PHP_EOL;
        } else {
            $data = json_stringify($message) . PHP_EOL;
        }

        LocalFS::fileAppend($this->file ?: '@data/fileMailer/mailer_' . date('ymd') . '.log', $data);

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}