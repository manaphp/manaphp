<?php
declare(strict_types=1);
/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Message;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class Swift extends AbstractMailer
{
    protected string $uri;
    protected string $encryption;
    protected string $host;
    protected int $port;
    protected string $username;
    protected string $password;

    public function __construct(string $uri)
    {
        $this->uri = $uri;

        $parts = parse_url($uri);

        $scheme = $parts['scheme'];

        $this->host = $parts['host'];
        if (isset($parts['port'])) {
            $this->port = (int)$parts['port'];
        } else {
            $this->port = $scheme === 'smtp' ? 25 : 465;
        }

        if ($scheme === 'smtp') {
            $encryption = null;
        } elseif ($scheme === 'tls') {
            $encryption = 'tls';
        } elseif ($scheme === 'ssl') {
            $encryption = 'ssl';
        } else {
            throw new NotSupportedException(['`:scheme` scheme is not known', 'scheme' => $scheme]);
        }
        $this->encryption = $encryption;

        if (isset($parts['user'])) {
            if (str_contains($parts['user'], '@')) {
                $this->from = $parts['user'];
            }
            $this->username = $parts['user'];
        }

        if (isset($parts['pass'])) {
            $this->password = $parts['pass'];
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            if (isset($query['log'])) {
                $this->log = $query['log'];
            }

            if (isset($query['from'])) {
                $this->from = $query['from'];
            }

            if (isset($query['to'])) {
                $this->to = $query['to'];
            }

            if (isset($query['user'])) {
                $this->username = $query['user'];
            }

            if (isset($query['password'])) {
                $this->password = $query['password'];
            }
        }
    }

    protected function sendInternal(Message $message, ?array &$failedRecipients = null): int
    {
        $swiftTransport = new Swift_SmtpTransport($this->host, $this->port, $this->encryption);
        if ($this->username) {
            $swiftTransport->setUsername($this->username);
        }

        if ($this->password) {
            $swiftTransport->setPassword($this->password);
        }

        $swift = new Swift_Mailer($swiftTransport);

        $swiftMessage = new Swift_Message();

        if ($charset = $message->getCharset()) {
            $swiftMessage->setCharset($charset);
        }

        $swiftMessage->setFrom($message->getFrom() ?: $this->from);

        if ($replyTo = $message->getReplyTo()) {
            $swiftMessage->setReplyTo($replyTo);
        }

        $swiftMessage->setTo($message->getTo() ?: $this->to);

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