<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Di\Attribute\Primary;
use ManaPHP\Mailing\Mailer\Message;

#[Primary('ManaPHP\Mailing\Mailer\Adapter\Smtp')]
interface MailerInterface
{
    public function compose(): Message;

    public function send(Message $message, ?array &$failedRecipients = null): int;
}