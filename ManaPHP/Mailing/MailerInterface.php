<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Mailing\Mailer\Message;

interface MailerInterface
{
    public function compose(): Message;

    public function send(Message $message, ?array &$failedRecipients = null): int;
}