<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Event;

use JsonSerializable;
use ManaPHP\Mailing\Mailer\Message;
use ManaPHP\Mailing\MailerInterface;
use Stringable;

class MailerSending implements JsonSerializable, Stringable
{
    public function __construct(
        public MailerInterface $mailer,
        public Message $message
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            'from'    => $this->message->getFrom(),
            'to'      => $this->message->getTo(),
            'bc'      => $this->message->getCc(),
            'bcc'     => $this->message->getBcc(),
            'subject' => $this->message->getSubject(),
        ];
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}