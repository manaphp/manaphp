<?php

namespace ManaPHP\Mailing;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

abstract class Mailer extends Component implements MailerInterface
{
    /**
     * @var string
     */
    protected $log;

    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $to;

    /**
     * @return \ManaPHP\Mailing\Mailer\Message
     */
    public function compose()
    {
        $message = $this->container->make('ManaPHP\Mailing\Mailer\Message');

        $message->setMailer($this);

        if ($this->from) {
            $message->setFrom($this->from);
        }
        if ($this->to) {
            $message->setFrom($this->to);
        }

        return $message;
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    abstract protected function sendInternal($message, &$failedRecipients = null);

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    public function send($message, &$failedRecipients = null)
    {
        if ($this->log) {
            LocalFS::fileAppend($this->log, json_stringify($message) . PHP_EOL);
        }

        $failedRecipients = [];

        $message->setMailer($this);
        $this->fireEvent('mailer:sending', compact('message'));
        $r = $this->self->sendInternal($message, $failedRecipients);
        $this->fireEvent('mailer:sent', compact('message', 'failedRecipients'));

        return $r;
    }
}