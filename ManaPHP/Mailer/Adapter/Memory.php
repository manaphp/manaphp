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

    protected function _send($message, &$failedRecipients = null)
    {
        $this->_message = $message;

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}