<?php

namespace ManaPHP\Mailing\Mailer;

use ManaPHP\Event\EventArgs;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('mailer:sending', [$this, 'onSending']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onSending(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Mailing\Mailer\Message $message */
        $message = $eventArgs->data['message'];

        if ($this->verbose) {
            $this->logger->debug(['From: ', $message->getFrom()]);
            $this->logger->debug(['To: ', $message->getTo()]);
            $this->logger->debug(['Cc:', $message->getCc()]);
            $this->logger->debug(['Bcc: ', $message->getBcc()]);
            $this->logger->debug(['Subject: ', $message->getSubject()]);
        } else {
            $this->logger->debug(['To: ', $message->getTo()]);
        }
    }
}