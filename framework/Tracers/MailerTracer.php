<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Mailing\Mailer\Event\MailerSending;
use Psr\Log\LoggerInterface;

class MailerTracer
{
    #[Inject] protected LoggerInterface $logger;

    #[Value] protected bool $verbose = true;

    public function onSending(#[Event] MailerSending $event): void
    {
        $message = $event->message;

        if ($this->verbose) {
            $this->logger->debug('From: {0}', [$message->getFrom(), 'category' => 'mailer.sending']);
            $this->logger->debug('To: {0}', [$message->getTo(), 'category' => 'mailer.sending']);
            $this->logger->debug('Cc: {0}', [$message->getCc(), 'category' => 'mailer.sending']);
            $this->logger->debug('Bcc: {0}', [$message->getBcc(), 'category' => 'mailer.sending']);
            $this->logger->debug('Subject: {0}', [$message->getSubject(), 'category' => 'mailer.sending']);
        } else {
            $this->logger->debug('To: {0}', [$message->getTo(), 'category' => 'mailer.sending']);
        }
    }
}