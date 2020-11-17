<?php

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Mailing\Mailer;

class Memory extends Mailer
{
    /**
     * @var \ManaPHP\Mailing\Mailer\Message
     */
    protected $_message;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
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
     * @return \ManaPHP\Mailing\Mailer\Message
     */
    public function getLastMessage()
    {
        return $this->_message;
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    protected function _send($message, &$failedRecipients = null)
    {
        $this->_message = $message;

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}