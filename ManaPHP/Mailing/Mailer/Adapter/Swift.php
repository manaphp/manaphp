<?php
/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Mailing\Mailer;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class Swift extends Mailer
{
    /**
     * @var string
     */
    protected $_url;

    /**
     * @var string
     */
    protected $_encryption;

    /**
     * @var string
     */
    protected $_host;

    /**
     * @var int
     */
    protected $_port;

    /**
     * @var string
     */
    protected $_username;

    /**
     * @var string
     */
    protected $_password;

    /**
     * @param string $url
     */
    public function __construct($url)
    {
        $this->_url = $url;

        $parts = parse_url($url);

        $scheme = $parts['scheme'];

        $this->_host = $parts['host'];
        if (isset($parts['port'])) {
            $this->_port = (int)$parts['port'];
        } else {
            $this->_port = $scheme === 'smtp' ? 25 : 465;
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
        $this->_encryption = $encryption;

        if (isset($parts['user'])) {
            if (str_contains($parts['user'], '@')) {
                $this->_from = $parts['user'];
            }
            $this->_username = $parts['user'];
        }

        if (isset($parts['pass'])) {
            $this->_password = $parts['pass'];
        }

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
                $this->_username = $query['user'];
            }

            if (isset($query['password'])) {
                $this->_password = $query['password'];
            }
        }
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     */
    protected function _send($message, &$failedRecipients = null)
    {
        $swiftTransport = new Swift_SmtpTransport($this->_host, $this->_port, $this->_encryption);
        if ($this->_username) {
            $swiftTransport->setUsername($this->_username);
        }

        if ($this->_password) {
            $swiftTransport->setPassword($this->_password);
        }

        $swift = new Swift_Mailer($swiftTransport);

        $swiftMessage = new Swift_Message();

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

        foreach ($message->getAttachments() as $at) {
            $swiftAttachment = new Swift_Attachment($at['data'], $at['file'], $at['contentType']);
            $swiftAttachment->setId($at['cid']);
            $swiftMessage->attach($swiftAttachment);
        }

        return $swift->send($swiftMessage, $failedRecipients);
    }
}