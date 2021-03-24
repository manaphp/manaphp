<?php

namespace ManaPHP\Mailing\Mailer;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Tracing\Tracer
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