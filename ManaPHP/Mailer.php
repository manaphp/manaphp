<?php
namespace ManaPHP;

use ManaPHP\Mailer\Message;

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
     * Mailer constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['log'])) {
            $this->_log = $options['log'];
        }

        if (isset($options['from'])) {
            $this->_from = $options['from'];
        }

        if (isset($options['to'])) {
            $this->_to = $options['to'];
        }
    }

    /**
     * @return \ManaPHP\Mailer\Message
     */
    public function compose()
    {
        $message = new Message();
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
            $data = '<' . date(DATE_ATOM) . '> ' . json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
            $this->filesystem->fileAppend($this->_log, $data);
        }

        $failedRecipients = [];

        $message->setMailer($this);
        $this->fireEvent('mailer:beforeSend', ['message' => $message]);
        $r = $this->_send($message, $failedRecipients);
        $this->fireEvent('mailer:afterSend', ['message' => $message, 'failedRecipients' => $failedRecipients]);

        return $r;
    }
}