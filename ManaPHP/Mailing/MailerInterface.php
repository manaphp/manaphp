<?php

namespace ManaPHP\Mailing;

interface MailerInterface
{
    /**
     * @return \ManaPHP\Mailing\Mailer\Message
     */
    public function compose();

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    public function send($message, &$failedRecipients = null);
}