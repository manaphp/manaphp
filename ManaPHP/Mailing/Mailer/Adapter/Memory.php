<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Message;

class Memory extends AbstractMailer
{
    protected Message $message;

    public function __construct(array $options = [])
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

    public function getLastMessage(): Message
    {
        return $this->message;
    }

    protected function sendInternal(Message $message, ?array &$failedRecipients = null): int
    {
        $this->message = $message;

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}