<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class MailerTracer extends Tracer
{
    public function listen(): void
    {
        $this->attachEvent('mailer:sending', [$this, 'onSending']);
    }

    public function onSending(EventArgs $eventArgs): void
    {
        /** @var \ManaPHP\Mailing\Mailer\Message $message */
        $message = $eventArgs->data['message'];

        if ($this->verbose) {
            $this->debug(['From: ', $message->getFrom()], 'mailer.sending');
            $this->debug(['To: ', $message->getTo()], 'mailer.sending');
            $this->debug(['Cc:', $message->getCc()], 'mailer.sending');
            $this->debug(['Bcc: ', $message->getBcc()], 'mailer.sending');
            $this->debug(['Subject: ', $message->getSubject()], 'mailer.sending');
        } else {
            $this->debug(['To: ', $message->getTo()], 'mailer.sending');
        }
    }
}