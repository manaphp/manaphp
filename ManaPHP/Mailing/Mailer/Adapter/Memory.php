<?php

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Mailing\Mailer;

class Memory extends Mailer
{
    /**
     * @var \ManaPHP\Mailing\Mailer\Message
     */
    protected $message;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
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
     * @return \ManaPHP\Mailing\Mailer\Message
     */
    public function getLastMessage()
    {
        return $this->message;
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    protected function sendInternal($message, &$failedRecipients = null)
    {
        $this->message = $message;

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}