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
     * @param string $url
     */
    public function __construct($url)
    {
        $this->_url = $url;

        $parts = parse_url($url);

        $scheme = $parts['scheme'];

        $host = $parts['host'];
        if (isset($parts['port'])) {
            $port = (int)$parts['port'];
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

        if (isset($parts['user'])) {
            if (strpos($parts['user'], '@') !== false) {
                $this->_from = $parts['user'];
            }
            $username = $parts['user'];
        } else {
            $username = null;
        }

        $password = isset($parts['pass']) ? $parts['pass'] : null;

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            if (isset($query['log'])) {
                $this->_log = $query['log'];
            }

            if (isset($query['from'])) {
                $this->_from = $query['from'];
            }

            if (isset($query['to'])) {
                $this->_to = $query['to'];
            }

            if (isset($query['user'])) {
                $username = $query['user'];
            }

            if (isset($query['password'])) {
                $password = $query['password'];
            }
        }

        $swiftTransport = new \Swift_SmtpTransport($host, $port, $encryption);
        if ($username) {
            $swiftTransport->setUsername($username);
        }

        if ($password) {
            $swiftTransport->setPassword($password);
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