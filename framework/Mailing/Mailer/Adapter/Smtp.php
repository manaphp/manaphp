<?php

declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Alias\Path;
use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Adapter\Exception\AuthenticationException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\BadResponseException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\ConnectionException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException;
use ManaPHP\Mailing\Mailer\Message;
use Psr\Log\LoggerInterface;
use function array_merge;
use function base64_encode;
use function bin2hex;
use function chunk_split;
use function count;
use function date;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function fwrite;
use function in_array;
use function is_file;
use function is_int;
use function is_string;
use function json_stringify;
use function key;
use function mime_content_type;
use function mkdir;
use function parse_str;
use function parse_url;
use function preg_match;
use function random_bytes;
use function rtrim;
use function str_contains;
use function str_replace;
use function strlen;
use function time;

class Smtp extends AbstractMailer implements ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected LoggerInterface $logger;

    #[Autowired] protected string $uri;
    protected string $scheme;
    protected string $host;
    protected int $port;
    protected string $username;
    protected string $password;
    protected int $timeout = 3;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
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
                $this->from = $query['user'];
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

    public function getContext(): SmtpContext
    {
        return $this->contextManager->getContext($this);
    }

    protected function connect(): mixed
    {
        $context = $this->getContext();

        if ($context->socket) {
            return $context->socket;
        }

        $uri = ($this->scheme === 'smtp' ? '' : "$this->scheme://") . $this->host;
        if (!$socket = fsockopen($uri, $this->port, $errno, $errstr, $this->timeout)) {
            throw new ConnectionException(
                'Failed to connect to mail server "{uri}:{port}": {errstr}',
                ['uri' => $uri, 'port' => $this->port, 'errstr' => $errstr]
            );
        }

        $response = fgets($socket);
        list($code,) = explode(' ', $response, 2);
        if ($code !== '220') {
            throw new ConnectionException('SMTP server response "{response}" does not indicate ready state (expected 220).', ['response' => trim($response)]);
        }

        $time = time();
        $context->file = Path::resolve('@runtime/mail/{date}/{time}_{rand}.log', [
            'date' => date('ymd', $time),
            'time' => date('ymd_His', $time),
            'rand' => bin2hex(random_bytes(8))
        ]);

        /** @noinspection MkdirRaceConditionInspection */
        @mkdir(dirname($context->file), 0777, true);

        return $context->socket = $socket;
    }

    protected function transmit(string $str, ?array $expected = null): array
    {
        $this->writeLine($str);

        do {
            $response = $this->readLine();
        } while ($response[3] !== ' ');

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
            throw new BadResponseException('Unexpected SMTP response code {code}, expected one of [{expected}].', ['code' => $code, 'expected' => implode(', ', $expected)]);
        }

        return [$code, $message];
    }

    protected function writeLine(?string $data = null): static
    {
        $context = $this->getContext();

        if ($data !== null) {
            if (fwrite($context->socket, $data) === false) {
                throw new TransmitException('Failed to send data to mail server "{uri}".', ['uri' => $this->uri]);
            }
            file_put_contents($context->file, $data, FILE_APPEND);
        }

        file_put_contents($context->file, PHP_EOL, FILE_APPEND);

        if (fwrite($context->socket, "\r\n") === false) {
            throw new TransmitException('Failed to send line break to mail server "{uri}".', ['uri' => $this->uri]);
        }

        return $this;
    }

    protected function readLine(): string
    {
        $context = $this->getContext();

        if (($str = fgets($context->socket)) === false) {
            throw new TransmitException('Failed to receive response from mail server "{uri}".', ['uri' => $this->uri]);
        }

        file_put_contents($context->file, str_replace("\r\n", PHP_EOL, $str), FILE_APPEND);
        return $str;
    }

    protected function sendTextBody(string $textBody): static
    {
        $this->writeLine('Content-Type: text/plain; charset=utf-8');
        $this->writeLine('Content-Length: ' . strlen($textBody));
        $this->writeLine('Content-Transfer-Encoding: base64');
        $this->writeLine();
        $this->writeLine(chunk_split(base64_encode($textBody), 983));

        return $this;
    }

    protected function sendHtmlBody(string $htmlBody, ?string $boundary = null): static
    {
        if (preg_match('#<meta http-equiv="Content-Type" content="([^"]+)">#i', $htmlBody, $match)) {
            $contentType = $match[1];
        } else {
            $contentType = 'text/html; charset=utf-8';
        }

        if ($boundary) {
            $this->writeLine();
            $this->writeLine("--$boundary");
        }
        $this->writeLine('Content-Type: ' . $contentType);
        $this->writeLine('Content-Length: ' . strlen($htmlBody));
        $this->writeLine('Content-Transfer-Encoding: base64');
        $this->writeLine();
        $this->writeLine(chunk_split(base64_encode($htmlBody), 983));

        return $this;
    }

    protected function sendAttachments(array $attachments, string $boundary): static
    {
        foreach ($attachments as $attachment) {
            $file = Path::resolve($attachment['file']);
            if (!is_file($file)) {
                throw new InvalidValueException('Email attachment file "{file}" does not exist or is not accessible.', ['file' => $file]);
            }

            $this->writeLine()
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

    protected function sendEmbeddedFiles(array $embeddedFiles, string $boundary): static
    {
        foreach ($embeddedFiles as $embeddedFile) {
            if (!is_file($file = Path::resolve($embeddedFile['file']))) {
                throw new InvalidValueException('Email inline attachment file "{file}" does not exist or is not accessible.', ['file' => $file]);
            }
            $this->writeLine()
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

    protected function encode(string $str): string
    {
        return '=?utf-8?B?' . base64_encode($str) . '?=';
    }

    protected function sendAddresses(string $type, array $addresses): static
    {
        foreach ($addresses as $k => $v) {
            if (is_int($k)) {
                $this->writeLine("$type: <$v>");
            } else {
                $this->writeLine("$type: " . $this->encode($v) . " <$k>");
            }
        }
        return $this;
    }

    /**
     * @throws BadResponseException
     * @throws TransmitException
     * @throws ConnectionException
     * @throws AuthenticationException
     */
    protected function sendInternal(Message $message, ?array &$failedRecipients = null): int
    {
        $this->connect();

        $this->transmit('HELO localhost', [250]);
        if ($this->password) {
            $this->transmit('AUTH LOGIN', [334]);

            list($code, $msg) = $this->transmit(base64_encode($this->username));
            if ($code !== 334) {
                throw new AuthenticationException('SMTP password rejected for server "{uri}": {code} {msg}.', ['uri' => $this->uri, 'code' => $code, 'msg' => $msg]);
            }
            list($code, $msg) = $this->transmit(base64_encode($this->password));
            if ($code !== 235) {
                throw new AuthenticationException('SMTP password rejected for server "{uri}": {code} {msg}.', ['uri' => $this->uri, 'code' => $code, 'msg' => $msg]);
            }
        }

        $from = $message->getFrom();
        $this->transmit('MAIL FROM:<' . ($from[0] ?? key($from)) . '>', [250]);

        $to = $message->getTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        $success = 0;
        foreach (array_merge($to, $cc, $bcc) as $k => $v) {
            $address = is_string($k) ? $k : $v;
            list($code, $msg) = $this->transmit("RCPT TO:<$address>");
            if ($code !== 250) {
                if ($failedRecipients !== null) {
                    $failedRecipients[] = $address;
                }
                $this->logger->info(\ManaPHP\Logging\Message::of('mailer.send', 'Failed Recipient To <{0}>: {1}'), [$address, $msg]);
            } else {
                $success++;
            }
        }

        if (!$success) {
            $addresses = array_merge($message->getTo(), $message->getCc(), $message->getBcc());
            $this->logger->info(\ManaPHP\Logging\Message::of('mailer.send', 'Send Failed: {0}'), [json_stringify($addresses)]);
            return $success;
        }

        $this->transmit('DATA', [354]);

        $this->sendAddresses('From', $from);
        $this->sendAddresses('To', $to);
        $this->sendAddresses('Cc', $cc);
        $this->sendAddresses('Reply-To', $message->getReplyTo());
        $this->writeLine('Subject: ' . $this->encode($message->getSubject()));
        $this->writeLine('MIME-Version: 1.0');

        $htmlBody = $message->getHtmlBody();
        $boundary = bin2hex(random_bytes(16));
        if (!$htmlBody) {
            if ($textBody = $message->getTextBody()) {
                $this->sendTextBody($textBody);
            } else {
                throw new InvalidValueException('Email message must contain either HTML body or text body, but both are missing.');
            }
        } elseif ($attachments = $message->getAttachments()) {
            $this->writeLine('Content-Type: multipart/mixed;');
            $this->writeLine("\tboundary=$boundary");
            $this->sendHtmlBody($htmlBody, $boundary);
            if ($embeddedFiles = $message->getEmbeddedFiles()) {
                $this->sendEmbeddedFiles($embeddedFiles, $boundary);
            }
            $this->sendAttachments($attachments, $boundary);
            $this->writeLine("--$boundary--");
        } elseif ($embeddedFiles = $message->getEmbeddedFiles()) {
            $this->writeLine('Content-Type: multipart/related;');
            $this->writeLine("\tboundary=$boundary");
            $this->sendHtmlBody($htmlBody, $boundary);
            $this->sendEmbeddedFiles($embeddedFiles, $boundary);
            $this->writeLine("--$boundary--");
        } else {
            $this->sendHtmlBody($htmlBody);
        }

        $this->transmit("\r\n.\r\n", [250]);
        $this->transmit('QUIT', [221, 421]);

        return $success;
    }
}
