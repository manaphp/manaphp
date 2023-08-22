<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Mailing\Mailer\Event\MailerSending;
use ManaPHP\Tracer;

class MailerTracer extends Tracer
{
    public function onSending(#[Event] MailerSending $event): void
    {
        $message = $event->message;

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