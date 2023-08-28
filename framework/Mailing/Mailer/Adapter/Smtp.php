<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Adapter\Exception\AuthenticationException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\BadResponseException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\ConnectionException;
use ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException;
use ManaPHP\Mailing\Mailer\Message;
use Psr\Log\LoggerInterface;

class Smtp extends AbstractMailer
{
    use ContextTrait;

    #[Inject] protected AliasInterface $alias;
    #[Inject] protected LoggerInterface $logger;

    #[Value] protected string $uri;
    protected string $scheme;
    protected string $host;
    protected int $port;
    protected string $username;
    protected string $password;
    protected int $timeout = 3;

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

    protected function connect(): mixed
    {
        /** @var SmtpContext $context */
        $context = $this->getContext();

        if ($context->socket) {
            return $context->socket;
        }

        $uri = ($this->scheme === 'smtp' ? '' : "$this->scheme://") . $this->host;
        if (!$socket = fsockopen($uri, $this->port, $errno, $errstr, $this->timeout)) {
            throw new ConnectionException(['connect to `{1}:{2}` mailer server failed: {3}', $uri, $this->port, $errstr]
            );
        }

        $response = fgets($socket);
        list($code,) = explode(' ', $response, 2);
        if ($code !== '220') {
            throw new ConnectionException(['connection protocol is not be recognized: {1}', $response]);
        }

        $context->file = $this->alias->resolve('@runtime/mail/{ymd}/{ymd_His_}{16}.log');

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
            throw new BadResponseException(['response is not expected: {response}', 'response' => $response]);
        }

        return [$code, $message];
    }

    protected function writeLine(?string $data = null): static
    {
        /** @var SmtpContext $context */
        $context = $this->getContext();

        if ($data !== null) {
            if (fwrite($context->socket, $data) === false) {
                throw new TransmitException(['send data failed: {uri}', 'uri' => $this->uri]);
            }
            file_put_contents($context->file, $data, FILE_APPEND);
        }

        file_put_contents($context->file, PHP_EOL, FILE_APPEND);

        if (fwrite($context->socket, "\r\n") === false) {
            throw new TransmitException(['send data failed: {uri}', 'uri' => $this->uri]);
        }

        return $this;
    }

    protected function readLine(): string
    {
        /** @var SmtpContext $context */
        $context = $this->getContext();

        if (($str = fgets($context->socket)) === false) {
            throw new TransmitException(['receive data failed: {uri}', 'uri' => $this->uri]);
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
            $file = $this->alias->resolve($attachment['file']);
            if (!is_file($file)) {
                throw new InvalidValueException(['`{file}` attachment file is not exists', 'file' => $file]);
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
            if (!is_file($file = $this->alias->resolve($embeddedFile['file']))) {
                throw new InvalidValueException(['`{file}` inline file is not exists', 'file' => $file]);
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
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\BadResponseException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\TransmitException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\ConnectionException
     * @throws \ManaPHP\Mailing\Mailer\Adapter\Exception\AuthenticationException
     */
    protected function sendInternal(Message $message, ?array &$failedRecipients = null): int
    {
        $this->connect();

        $this->transmit('HELO localhost', [250]);
        if ($this->password) {
            $this->transmit('AUTH LOGIN', [334]);

            list($code, $msg) = $this->transmit(base64_encode($this->username));
            if ($code !== 334) {
                throw new AuthenticationException(['authenticate with `{1}` failed: {2} {3}', $this->uri, $code, $msg]);
            }
            list($code, $msg) = $this->transmit(base64_encode($this->password));
            if ($code !== 235) {
                throw new AuthenticationException(['authenticate with `{1}` failed: {2} {3}', $this->uri, $code, $msg]);
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
                $this->logger->info('Failed Recipient To <{0}>: {1}', [$address, $msg, 'category' => 'mailer.send']);
            } else {
                $success++;
            }
        }

        if (!$success) {
            $addresses = array_merge($message->getTo(), $message->getCc(), $message->getBcc());
            $this->logger->info('Send Failed: {0}', [json_stringify($addresses), 'category' => 'mailer.send']);
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
                throw new InvalidValueException('mail is invalid: neither html body nor text body is exist.');
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