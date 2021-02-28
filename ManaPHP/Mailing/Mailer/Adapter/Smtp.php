<?php

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Mailing\Mailer;
use ManaPHP\Mailing\Mailer\Adapter\Exception\AuthenticationException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\BadResponseException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\ConnectionException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class SmtpContext implements Inseparable
{
    public $socket;
    public $file;
}

/**
 * @property-read \ManaPHP\AliasInterface                     $alias
 * @property-read \ManaPHP\Logging\LoggerInterface            $logger
 * @property-read \ManaPHP\Mailing\Mailer\Adapter\SmtpContext $context
 */
class Smtp extends Mailer
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var int
     */
    protected $timeout = 3;

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;

        $parts = parse_url($this->uri);

        $this->scheme = $parts['scheme'];
        $this->host = $parts['host'];

        if (isset($parts['port'])) {
            $this->port = (int)$parts['port'];
        } else {
            $this->port = $this->scheme === 'smtp' ? 25 : 465;
        }

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

            if (isset($query['user'])) {
                $this->username = $query['user'];
            }

            if (isset($query['password'])) {
                $this->password = $query['password'];
            }

            if (isset($query['from'])) {
                $this->from = $query['from'];
            }

            if (isset($query['to'])) {
                $this->to = $query['to'];
            }

            if (isset($query['log'])) {
                $this->log = $query['log'];
            }

            if (isset($query['timeout'])) {
                $this->timeout = (int)$query['timeout'];
            }
        }
    }

    /**
     * @return resource
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\ConnectionException
     */
    protected function connect()
    {
        $context = $this->context;

        if ($context->socket) {
            return $context->socket;
        }

        $uri = ($this->scheme === 'smtp' ? '' : "$this->scheme://") . $this->host;
        if (!$socket = fsockopen($uri, $this->port, $errno, $errstr, $this->timeout)) {
            throw new ConnectionException(['connect to `:1::2` mailer server failed: :3', $uri, $this->port, $errstr]);
        }

        $response = fgets($socket);
        list($code,) = explode(' ', $response, 2);
        if ($code !== '220') {
            throw new ConnectionException(['connection protocol is not be recognized: %s', $response]);
        }

        $context->file = $this->alias->resolve('@data/mail/{ymd}/{ymd_His_}{16}.log');

        /** @noinspection MkdirRaceConditionInspection */
        @mkdir(dirname($context->file), 0777, true);

        return $context->socket = $socket;
    }

    /**
     * @param string $str
     * @param int[]  $expected
     *
     * @return array
     *
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\BadResponseException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function transmit($str, $expected = null)
    {
        $this->self->writeLine($str);

        $response = $this->self->readLine();
        $parts = explode(' ', $response, 2);
        if (count($parts) === 2) {
            list($code, $message) = $parts;
            $message = rtrim($message);
        } else {
            $code = $parts[0];
            $message = null;
        }

        $code = (int)$code;
        if ($expected && !in_array($code, $expected, true)) {
            throw new BadResponseException(['response is not expected: :response', 'response' => $response]);
        }

        return [$code, $message];
    }


    /**
     * @param string $data
     *
     * @return static
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function writeLine($data = null)
    {
        $context = $this->context;

        if ($data !== null) {
            if (fwrite($context->socket, $data) === false) {
                throw new TransmitException(['send data failed: :uri', 'uri' => $this->uri]);
            }
            file_put_contents($context->file, $data, FILE_APPEND);
        }

        file_put_contents($context->file, PHP_EOL, FILE_APPEND);

        if (fwrite($context->socket, "\r\n") === false) {
            throw new TransmitException(['send data failed: :uri', 'uri' => $this->uri]);
        }

        return $this;
    }

    /**
     * @return string
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function readLine()
    {
        $context = $this->context;

        if (($str = fgets($context->socket)) === false) {
            throw new TransmitException(['receive data failed: :uri', 'uri' => $this->uri]);
        }

        file_put_contents($context->file, str_replace("\r\n", PHP_EOL, $str), FILE_APPEND);
        return $str;
    }

    /**
     * @param string $textBody
     *
     * @return static
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function sendTextBody($textBody)
    {
        $this->self->writeLine('Content-Type: text/plain; charset=utf-8');
        $this->self->writeLine('Content-Length: ' . strlen($textBody));
        $this->self->writeLine('Content-Transfer-Encoding: base64');
        $this->self->writeLine();
        $this->self->writeLine(chunk_split(base64_encode($textBody), 983));

        return $this;
    }

    /**
     * @param string $htmlBody
     * @param string $boundary
     *
     * @return static
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function sendHtmlBody($htmlBody, $boundary = null)
    {
        if (preg_match('#<meta http-equiv="Content-Type" content="([^"]+)">#i', $htmlBody, $match)) {
            $contentType = $match[1];
        } else {
            $contentType = 'text/html; charset=utf-8';
        }

        if ($boundary) {
            $this->self->writeLine();
            $this->self->writeLine("--$boundary");
        }
        $this->self->writeLine('Content-Type: ' . $contentType);
        $this->self->writeLine('Content-Length: ' . strlen($htmlBody));
        $this->self->writeLine('Content-Transfer-Encoding: base64');
        $this->self->writeLine();
        $this->self->writeLine(chunk_split(base64_encode($htmlBody), 983));

        return $this;
    }

    /**
     * @param array  $attachments
     * @param string $boundary
     *
     * @return static
     * @throws \ManaPHP\Exception\InvalidValueException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function sendAttachments($attachments, $boundary)
    {
        foreach ($attachments as $attachment) {
            $file = $this->alias->resolve($attachment['file']);
            if (!is_file($file)) {
                throw new InvalidValueException(['`:file` attachment file is not exists', 'file' => $file]);
            }

            $this->self->writeLine()
                ->writeLine("--$boundary")
                ->writeLine('Content-Type: ' . mime_content_type($file))
                ->writeLine('Content-Length: ' . filesize($file))
                ->writeLine('Content-Disposition: attachment; filename="' . $attachment['name'] . '"')
                ->writeLine('Content-Transfer-Encoding: base64')
                ->writeLine()
                ->writeLine(chunk_split(base64_encode(file_get_contents($file)), 983));
        }

        return $this;
    }

    /**
     * @param array[] $embeddedFiles
     * @param string  $boundary
     *
     * @return static
     *
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function sendEmbeddedFiles($embeddedFiles, $boundary)
    {
        foreach ($embeddedFiles as $embeddedFile) {
            if (!is_file($file = $this->alias->resolve($embeddedFile['file']))) {
                throw new InvalidValueException(['`:file` inline file is not exists', 'file' => $file]);
            }
            $this->self->writeLine()
                ->writeLine("--$boundary")
                ->writeLine('Content-Type: ' . mime_content_type($file))
                ->writeLine('Content-Length: ' . filesize($file))
                ->writeLine('Content-ID: <' . $embeddedFile['cid'] . '>')
                ->writeLine('Content-Disposition: inline; filename="' . $embeddedFile['name'] . '"')
                ->writeLine('Content-Transfer-Encoding: base64')
                ->writeLine()
                ->writeLine(chunk_split(base64_encode(file_get_contents($file)), 983));
        }

        return $this;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function encode($str)
    {
        return '=?utf-8?B?' . base64_encode($str) . '?=';
    }

    /**
     * @param string $type
     * @param array  $addresses
     *
     * @return static
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     */
    protected function sendAddresses($type, $addresses)
    {
        foreach ($addresses as $k => $v) {
            if (is_int($k)) {
                $this->self->writeLine("$type: <$v>");
            } else {
                $this->self->writeLine("$type: " . $this->self->encode($v) . " <$k>");
            }
        }
        return $this;
    }

    /**
     * @param \ManaPHP\Mailing\Mailer\Message $message
     * @param array                           $failedRecipients
     *
     * @return int
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\BadResponseException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\ConnectionException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\AuthenticationException
     */
    protected function sendInternal($message, &$failedRecipients = null)
    {
        $this->self->connect();

        $this->self->transmit('HELO localhost', [250]);
        if ($this->password) {
            $this->self->transmit('AUTH LOGIN', [334]);

            list($code, $msg) = $this->self->transmit(base64_encode($this->username));
            if ($code !== 334) {
                throw new AuthenticationException(['authenticate with `%s` failed: %d %s', $this->uri, $code, $msg]);
            }
            list($code, $msg) = $this->self->transmit(base64_encode($this->password));
            if ($code !== 235) {
                throw new AuthenticationException(['authenticate with `%s` failed: %d %s', $this->uri, $code, $msg]);
            }
        }

        $from = $message->getFrom();
        $this->self->transmit('MAIL FROM:<' . ($from[0] ?? key($from)) . '>', [250]);

        $to = $message->getTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        $success = 0;
        foreach (array_merge($to, $cc, $bcc) as $k => $v) {
            $address = is_string($k) ? $k : $v;
            list($code, $msg) = $this->self->transmit("RCPT TO:<$address>");
            if ($code !== 250) {
                if ($failedRecipients !== null) {
                    $failedRecipients[] = $address;
                }
                $this->logger->info(
                    ['Failed Recipient To <:address>: :msg', 'address' => $address, 'msg' => $msg],
                    'mailer.send'
                );
            } else {
                $success++;
            }
        }

        if (!$success) {
            $this->logger->info(
                ['Send Failed:', array_merge($message->getTo(), $message->getCc(), $message->getBcc())],
                'mailer.send'
            );
            return $success;
        }

        $this->self->transmit('DATA', [354]);

        $this->self->sendAddresses('From', $from);
        $this->self->sendAddresses('To', $to);
        $this->self->sendAddresses('Cc', $cc);
        $this->self->sendAddresses('Reply-To', $message->getReplyTo());
        $this->self->writeLine('Subject: ' . $this->self->encode($message->getSubject()));
        $this->self->writeLine('MIME-Version: 1.0');

        $htmlBody = $message->getHtmlBody();
        $boundary = bin2hex(random_bytes(16));
        if (!$htmlBody) {
            if ($textBody = $message->getTextBody()) {
                $this->self->sendTextBody($textBody);
            } else {
                throw new InvalidValueException('mail is invalid: neither html body nor text body is exist.');
            }
        } elseif ($attachments = $message->getAttachments()) {
            $this->self->writeLine('Content-Type: multipart/mixed;');
            $this->self->writeLine("\tboundary=$boundary");
            $this->self->sendHtmlBody($htmlBody, $boundary);
            /** @noinspection NotOptimalIfConditionsInspection */
            if ($embeddedFiles = $message->getEmbeddedFiles()) {
                $this->self->sendEmbeddedFiles($embeddedFiles, $boundary);
            }
            $this->self->sendAttachments($attachments, $boundary);
            $this->self->writeLine("--$boundary--");
        } elseif ($embeddedFiles = $message->getEmbeddedFiles()) {
            $this->self->writeLine('Content-Type: multipart/related;');
            $this->self->writeLine("\tboundary=$boundary");
            $this->self->sendHtmlBody($htmlBody, $boundary);
            $this->self->sendEmbeddedFiles($embeddedFiles, $boundary);
            $this->self->writeLine("--$boundary--");
        } else {
            $this->self->sendHtmlBody($htmlBody);
        }

        $this->self->transmit("\r\n.\r\n", [250]);
        $this->self->transmit('QUIT', [221, 421]);

        return $success;
    }
}