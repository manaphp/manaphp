<?php

namespace ManaPHP;

interface MailerInterface
{
    /**
     * @return \ManaPHP\Mailer\Message
     */
    public function compose();

    /**
     * @param \ManaPHP\Mailer\Message $message
     * @param array                   $failedRecipients
     *
     * @return int
     */
    public function send($message, &$failedRecipients = null);
}