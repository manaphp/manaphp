<?php

namespace ManaPHP\Mailing;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

abstract class Mailer extends Component implements MailerInterface
{
    /**
     * @var string
     */
    protected $_log;

    /**
     * @var string
     */
    protected $_from;

    /**
     * @var string
     */
    protected $_to;

    /**
     * @return \ManaPHP\Mailing\Mailer\Message
     */
    public function compose()
    {
        $message = $this->getInstance('ManaPHP\Mailing\Mailer\Message');

        $message->setMailer($this);

        if ($this->_from) {
            $message->setFrom($this->_from);
        }
        if ($this->_to) {
            $message->setFrom($this->_to);
        }

        return $message;
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    abstract protected function _send($message, &$failedRecipients = null);

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    public function send($message, &$failedRecipients = null)
    {
        if ($this->_log) {
            LocalFS::fileAppend($this->_log, json_stringify($message) . PHP_EOL);
        }

        $failedRecipients = [];

        $message->setMailer($this);
        $this->fireEvent('mailer:sending', ['message' => $message]);
        $r = $this->_send($message, $failedRecipients);
        $this->fireEvent('mailer:sent', ['message' => $message, 'failedRecipients' => $failedRecipients]);

        return $r;
    }
}