<?php
namespace ManaPHP\Mailer\Adapter;

use ManaPHP\Exception\InvalidUrlException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Mailer;
use ManaPHP\Mailer\Adapter\Exception\AuthenticationException;
use ManaPHP\Mailer\Adapter\Exception\BadResponseException;
use ManaPHP\Mailer\Adapter\Exception\ConnectionException;
use ManaPHP\Mailer\Adapter\Exception\TransmitException;

class Smtp extends Mailer
{
    /**
     * @var string
     */
    protected $_url;

    /**
     * @var string
     */
    protected $_scheme;

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
     * @var int
     */
    protected $_timeout = 10;

    /**
     * @var
     */
    protected $_socket;

    /**
     * @var string
     */
    protected $_file;

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

        $this->_scheme = $scheme = $parts['scheme'];
        $this->_host = $parts['host'];

        if (isset($parts['port'])) {
            $this->_port = $parts['port'];
        } else {
            $this->_port = $scheme === 'smtp' ? 25 : 465;
        }

        if (isset($parts['user'])) {
            if (strpos($parts['user'], '@')) {
                $this->_from = $parts['user'];
            }
            $this->_username = $parts['user'];
        }

        if (isset($parts['pass'])) {
            $this->_password = $parts['pass'];
        }
    }

    /**
     * @return resource
     * @throws \ManaPHP\Mailer\Adapter\Exception\ConnectionException
     */
    protected function _connect()
    {
        if ($this->_socket) {
            return $this->_socket;
        }

        $url = ($this->_scheme === 'smtp' ? '' : "$this->_scheme://") . $this->_host;
        if (!$socket = fsockopen($url, $this->_port, $errno, $errstr, $this->_timeout)) {
            throw new ConnectionException(['connect to `:1::2` mailer server failed: :3', $url, $this->_port, $errstr]);
        }

        $response = fgets($socket);
        list($code,) = explode(' ', $response, 2);
        if ($code !== '220') {
            throw new ConnectionException(['connection protocol is not be recognized: :message', 'message' => $response]);
        }

        $this->_file = $this->alias->resolve('@data/mail/{ymd}/{ymd_His_}{16}.log');

        /** @noinspection MkdirRaceConditionInspection */
        @mkdir(dirname($this->_file), 0777, true);

        return $this->_socket = $socket;
    }

    /**
     * @param string $str
     *
     * @param array
     *
     * @return array
     * @throws \ManaPHP\Mailer\Adapter\Exception\BadResponseException
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _transmit($str, $expected = null)
    {
        $this->_writeLine($str);

        $response = $this->_readLine();
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
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _writeLine($data = null)
    {
        if ($data !== null) {
            if (fwrite($this->_socket, $data) === false) {
                throw new TransmitException(['send data failed: :url', 'url' => $this->_url]);
            }
            file_put_contents($this->_file, $data, FILE_APPEND);
        }

        file_put_contents($this->_file, PHP_EOL, FILE_APPEND);

        if (fwrite($this->_socket, "\r\n") === false) {
            throw new TransmitException(['send data failed: :url', 'url' => $this->_url]);
        }

        return $this;
    }

    /**
     * @return string
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _readLine()
    {
        if (($str = fgets($this->_socket)) === false) {
            throw new TransmitException(['receive data failed: :url', 'url' => $this->_url]);
        }

        file_put_contents($this->_file, str_replace("\r\n", PHP_EOL, $str), FILE_APPEND);
        return $str;
    }

    /**
     * @param string $textBody
     *
     * @return static
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _sendTextBody($textBody)
    {
        $this->_writeLine('Content-Type: text/plain; charset=utf-8');
        $this->_writeLine('Content-Length: ' . strlen($textBody));
        $this->_writeLine('Content-Transfer-Encoding: base64');
        $this->_writeLine();
        $this->_writeLine(chunk_split(base64_encode($textBody), 983));

        return $this;
    }

    /**
     * @param string $htmlBody
     * @param string $boundary
     *
     * @return static
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _sendHtmlBody($htmlBody, $boundary = null)
    {
        $contentType = preg_match('#<meta http-equiv="Content-Type" content="([^"]+)">#i', $htmlBody, $match) ? $match[1] : 'text/html; charset=utf-8';

        if ($boundary) {
            $this->_writeLine();
            $this->_writeLine("--$boundary");
        }
        $this->_writeLine('Content-Type: ' . $contentType);
        $this->_writeLine('Content-Length: ' . strlen($htmlBody));
        $this->_writeLine('Content-Transfer-Encoding: base64');
        $this->_writeLine();
        $this->_writeLine(chunk_split(base64_encode($htmlBody), 983));

        return $this;
    }

    /**
     * @param array  $attachments
     * @param string $boundary
     *
     * @return static
     * @throws \ManaPHP\Exception\InvalidValueException
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _sendAttachments($attachments, $boundary)
    {
        foreach ($attachments as $attachment) {
            $file = $this->alias->resolve($attachment['file']);
            if (!is_file($file)) {
                throw new InvalidValueException(['`:file` attachment file is not exists', 'file' => $file]);
            }
            $this->_writeLine();
            $this->_writeLine("--$boundary");
            $this->_writeLine('Content-Type: ' . mime_content_type($file));
            $this->_writeLine('Content-Length: ' . filesize($file));
            $this->_writeLine('Content-Disposition: attachment; filename="' . $attachment['name'] . '"');
            $this->_writeLine('Content-Transfer-Encoding: base64');
            $this->_writeLine();
            $this->_writeLine(chunk_split(base64_encode(file_get_contents($file)), 983));
        }

        return $this;
    }

    /**
     * @param array[] $embeddedFiles
     * @param string  $boundary
     *
     * @return static
     *
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _sendEmbeddedFiles($embeddedFiles, $boundary)
    {
        foreach ($embeddedFiles as $embeddedFile) {
            if (!is_file($file = $this->alias->resolve($embeddedFile['file']))) {
                throw new InvalidValueException(['`:file` inline file is not exists', 'file' => $file]);
            }
            $this->_writeLine();
            $this->_writeLine("--$boundary");
            $this->_writeLine('Content-Type: ' . mime_content_type($file));
            $this->_writeLine('Content-Length: ' . filesize($file));
            $this->_writeLine('Content-ID: <' . $embeddedFile['cid'] . '>');
            $this->_writeLine('Content-Disposition: inline; filename="' . $embeddedFile['name'] . '"');
            $this->_writeLine('Content-Transfer-Encoding: base64');
            $this->_writeLine();
            $this->_writeLine(chunk_split(base64_encode(file_get_contents($file)), 983));
        }

        return $this;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function _encode($str)
    {
        return '=?utf-8?B?' . base64_encode($str) . '?=';
    }

    /**
     * @param string $type
     * @param array  $addresses
     *
     * @return static
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     */
    protected function _sendAddresses($type, $addresses)
    {
        foreach ($addresses as $k => $v) {
            if (is_int($k)) {
                $this->_writeLine("$type: <$v>");
            } else {
                $this->_writeLine("$type: " . $this->_encode($v) . " <$k>");
            }
        }
        return $this;
    }

    /**
     * @param \ManaPHP\Mailer\Message $message
     * @param array                   $failedRecipients
     *
     * @return int
     * @throws \ManaPHP\Mailer\Adapter\Exception\BadResponseException
     * @throws \ManaPHP\Mailer\Adapter\Exception\TransmitException
     * @throws \ManaPHP\Mailer\Adapter\Exception\ConnectionException
     * @throws \ManaPHP\Mailer\Adapter\Exception\AuthenticationException
     */
    protected function _send($message, &$failedRecipients = null)
    {
        $this->_connect();

        $this->_transmit('HELO localhost', [250]);
        if ($this->_password) {
            $this->_transmit('AUTH LOGIN', [334]);

            list($code, $msg) = $this->_transmit(base64_encode($this->_username));
            if ($code !== 334) {
                throw new AuthenticationException(['authenticate with `:url` failed: :message', 'url' => $this->_url, 'message' => $code . ' ' . $msg]);
            }
            list($code, $msg) = $this->_transmit(base64_encode($this->_password));
            if ($code !== 235) {
                throw new AuthenticationException(['authenticate with `:url` failed: :message', 'url' => $this->_url, 'message' => $code . ' ' . $msg]);
            }
        }

        $from = $message->getFrom();
        $this->_transmit('MAIL FROM:<' . (isset($from[0]) ? $from[0] : key($from)) . '>', [250]);

        $to = $message->getTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        $success = 0;
        foreach (array_merge($to, $cc, $bcc) as $k => $v) {
            $address = is_string($k) ? $k : $v;
            list($code, $msg) = $this->_transmit("RCPT TO:<$address>");
            if ($code !== 250) {
                if ($failedRecipients !== null) {
                    $failedRecipients[] = $address;
                }
                $this->logger->info(['Failed Recipient To <:address>: :msg', 'address' => $address, 'msg' => $msg], 'mailer.send');
            } else {
                $success++;
            }
        }

        if (!$success) {
            $this->logger->info(['Send Failed:', array_merge($message->getTo(), $message->getCc(), $message->getBcc())], 'mailer.send');
            return $success;
        }

        $this->_transmit('DATA', [354]);

        $this->_sendAddresses('From', $from);
        $this->_sendAddresses('To', $to);
        $this->_sendAddresses('Cc', $cc);
        $this->_sendAddresses('Reply-To', $message->getReplyTo());
        $this->_writeLine('Subject: ' . $this->_encode($message->getSubject()));
        $this->_writeLine('MIME-Version: 1.0');

        $htmlBody = $message->getHtmlBody();
        $boundary = md5(microtime(true) . mt_rand());
        if (!$htmlBody) {
            if ($textBody = $message->getTextBody()) {
                $this->_sendTextBody($textBody);
            } else {
                throw new InvalidValueException('mail is invalid: neither html body nor text body is exist.');
            }
        } elseif ($attachments = $message->getAttachments()) {
            $this->_writeLine('Content-Type: multipart/mixed;');
            $this->_writeLine("\tboundary=$boundary");
            $this->_sendHtmlBody($htmlBody, $boundary);
            /** @noinspection NotOptimalIfConditionsInspection */
            if ($embeddedFiles = $message->getEmbeddedFiles()) {
                $this->_sendEmbeddedFiles($embeddedFiles, $boundary);
            }
            $this->_sendAttachments($attachments, $boundary);
            $this->_writeLine("--$boundary--");
        } elseif ($embeddedFiles = $message->getEmbeddedFiles()) {
            $this->_writeLine('Content-Type: multipart/related;');
            $this->_writeLine("\tboundary=$boundary");
            $this->_sendHtmlBody($htmlBody, $boundary);
            $this->_sendEmbeddedFiles($embeddedFiles, $boundary);
            $this->_writeLine("--$boundary--");
        } else {
            $this->_sendHtmlBody($htmlBody);
        }

        $this->_transmit("\r\n.\r\n", [250]);
        $this->_transmit('QUIT', [221, 421]);

        return $success;
    }
}