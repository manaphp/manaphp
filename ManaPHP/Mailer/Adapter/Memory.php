<?php
namespace ManaPHP\Mailer\Adapter;

use ManaPHP\Mailer;

class Memory extends Mailer
{
    /**
     * @var \ManaPHP\Mailer\Message
     */
    protected $_message;

    /**
     * @return \ManaPHP\Mailer\Message
     */
    public function getLastMessage()
    {
        return $this->_message;
    }

    /**
     * @param \ManaPHP\Mailer\Message $message
     * @param array                   $failedRecipients
     *
     * @return int
     */
    protected function _send($message, &$failedRecipients = null)
    {
        $this->_message = $message;

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}