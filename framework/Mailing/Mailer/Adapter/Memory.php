<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Message;
use function count;

class Memory extends AbstractMailer
{
    protected Message $message;

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