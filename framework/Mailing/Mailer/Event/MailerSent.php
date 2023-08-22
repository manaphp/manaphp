<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Event;

use ManaPHP\Mailing\Mailer\Message;
use ManaPHP\Mailing\MailerInterface;

class MailerSent
{
    public function __construct(
        public MailerInterface $mailer,
        public Message $message,
        public array $failedRecipients,
    ) {

    }
}