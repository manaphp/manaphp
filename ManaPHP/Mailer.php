<?php
namespace ManaPHP;

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
     * @return \ManaPHP\Mailer\Message
     */
    public function compose()
    {
        $message = $this->_di->get('ManaPHP\Mailer\Message');

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
     * @param \ManaPHP\Mailer\Message $message
     * @param array                   $failedRecipients
     *
     * @return int
     */
    abstract protected function _send($message, &$failedRecipients = null);

    /**
     * @param \ManaPHP\Mailer\Message $message
     * @param array                   $failedRecipients
     *
     * @return int
     */
    public function send($message, &$failedRecipients = null)
    {
        if ($this->_log) {
            $this->filesystem->fileAppend($this->_log, json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
        }

        $this->logger->debug(['From: ', $message->getFrom()]);
        $this->logger->debug(['To: ', $message->getTo()]);
        $this->logger->debug(['Cc:', $message->getCc()]);
        $this->logger->debug(['Bcc: ', $message->getBcc()]);
        $this->logger->debug(['Subject: ', $message->getSubject()]);

        $failedRecipients = [];

        $message->setMailer($this);
        $this->eventsManager->fireEvent('mailer:beforeSend', $this, ['message' => $message]);
        $r = $this->_send($message, $failedRecipients);
        $this->eventsManager->fireEvent('mailer:afterSend', $this, ['message' => $message, 'failedRecipients' => $failedRecipients]);

        return $r;
    }
}