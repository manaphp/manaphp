<?php
namespace ManaPHP\Mailer\Adapter;

use ManaPHP\Exception\InvalidUrlException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Mailer;

class Swift extends Mailer
{
    /**
     * @var string
     */
    protected $_url;

    /**
     * @var \Swift_Mailer
     */
    protected $_swift;

    /**
     * Swift constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $this->_url = $options;
            parent::__construct([]);
        } else {
            if (!isset($options['url'])) {
                throw new MissingFieldException('url');
            }
            $this->_url = $options['url'];
            parent::__construct($options);
        }

        if (!$parts = parse_url($this->_url)) {
            throw new InvalidUrlException($this->_url);
        }

        $scheme = $parts['scheme'];

        $host = $parts['host'];
        if (isset($parts['port'])) {
            $port = $parts['port'];
        } else {
            $port = $scheme === 'smtp' ? 25 : 465;
        }

        if ($scheme === 'smtp') {
            $encryption = null;
        } elseif ($scheme === 'tls') {
            $encryption = 'tls';
        } elseif ($scheme === 'ssl') {
            $encryption = 'ssl';
        } else {
            throw new NotSupportedException('`:scheme` scheme is not known', ['scheme' => $scheme]);
        }

        $swiftTransport = new \Swift_SmtpTransport($host, $port, $encryption);
        if (isset($parts['user'])) {
            if (strpos($parts['user'], '@')) {
                $this->_from = $parts['user'];
            }
            $swiftTransport->setUsername($parts['user']);
        }
        if (isset($parts['pass'])) {
            $swiftTransport->setPassword($parts['pass']);
        }

        $this->_swift = new \Swift_Mailer($swiftTransport);
    }

    /**
     * @param \ManaPHP\Mailer\Message $message
     * @param array                   $failedRecipients
     *
     * @return int
     */
    protected function _send($message, &$failedRecipients = null)
    {
        $swiftMessage = new \Swift_Message();

        if ($charset = $message->getCharset()) {
            $swiftMessage->setCharset($charset);
        }

        $swiftMessage->setFrom($message->getFrom() ?: $this->_from);

        if ($replyTo = $message->getReplyTo()) {
            $swiftMessage->setReplyTo($replyTo);
        }

        $swiftMessage->setTo($message->getTo() ?: $this->_to);

        if ($cc = $message->getCc()) {
            $swiftMessage->setCc($cc);
        }

        if ($bcc = $message->getBcc()) {
            $swiftMessage->setBcc($bcc);
        }

        $swiftMessage->setSubject($message->getSubject());
        $swiftMessage->setBody($message->getHtmlBody());
        $swiftMessage->setContentType($message->getContentType());

        if ($priority = $message->getPriority()) {
            $swiftMessage->setPriority($priority);
        }

        foreach ($message->getAttachments() as $attachment) {
            $swiftMessage->attach(
                (new \Swift_Attachment($attachment['data'], $attachment['file'], $attachment['contentType']))->setId($attachment['cid']));
        }

        return $this->_swift->send($swiftMessage, $failedRecipients);
    }
}