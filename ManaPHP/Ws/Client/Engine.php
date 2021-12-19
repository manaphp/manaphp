<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Client;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Ws\ClientInterface;

class Engine extends Component implements EngineInterface
{
    protected string $endpoint;
    protected string $proxy;
    protected float $timeout = 3.0;
    protected string $protocol;
    protected bool $masking = true;
    protected string $origin;
    protected string $user_agent = 'manaphp/client';
    protected mixed $socket;
    protected ClientInterface $owner;

    public function __construct(array $options)
    {
        $this->endpoint = $options['endpoint'];

        if (isset($options['proxy'])) {
            $this->proxy = $options['proxy'];
        }

        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        if (isset($options['protocol'])) {
            $this->protocol = $options['protocol'];
        }

        if (isset($options['masking'])) {
            $this->masking = (bool)$options['masking'];
        }

        if (isset($options['origin'])) {
            $this->origin = $options['origin'];
        }

        if (isset($options['user_agent'])) {
            $this->user_agent = $options['user_agent'];
        }

        if (isset($options['owner'])) {
            $this->owner = $options['owner'];
        }
    }

    public function __clone()
    {
        $this->close();
    }

    public function setEndpoint(string $endpoint): static
    {
        if ($this->socket !== null) {
            $this->close();
        }

        $this->endpoint = $endpoint;

        return $this;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    protected function open(): mixed
    {
        if ($this->socket) {
            return $this->socket;
        }

        $parts = parse_url($this->endpoint);
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = $parts['port'] ?? ($scheme === 'ws' ? 80 : 443);

        if ($this->proxy) {
            $parts = parse_url($this->proxy);

            $proxy_scheme = $parts['scheme'];
            if ($proxy_scheme !== 'http' && $proxy_scheme !== 'https') {
                throw new NotSupportedException('only support http and https proxy');
            }
            $server_host = ($proxy_scheme === 'http' ? 'tcp' : 'ssl') . '://' . $parts['host'];
            $server_port = $parts['port'];
        } else {
            $server_host = ($scheme === 'ws' ? 'tcp' : 'ssl') . "://$host";
            $server_port = $port;
        }

        if (!$socket = @fsockopen($server_host, $server_port, $errno, $errmsg, $this->timeout)) {
            throw new ConnectionException($errmsg . ': ' . $this->endpoint, $errno);
        }

        stream_set_timeout($socket, (int)$this->timeout, ($this->timeout - (int)$this->timeout) * 1000);
        $path = ($scheme === 'ws' ? 'http' : 'https') . substr($this->endpoint, strpos($this->endpoint, ':'));

        $key = base64_encode(random_bytes(16));
        $headers = "GET $path HTTP/1.1\r\n" .
            "Host: $host:$port\r\n" .
            "Sec-WebSocket-Key: $key\r\n" .
            "Connection: Upgrade\r\n" .
            "User-Agent: $this->user_agent\r\n" .
            "Upgrade: Websocket\r\n";

        $headers .= $this->origin ? "Origin: $this->origin\r\n" : '';
        $headers .= $this->protocol ? "Sec-WebSocket-Protocol: $this->protocol\r\n" : '';

        $headers .= "Sec-WebSocket-Version: 13\r\n\r\n";

        $this->sendInternal($socket, $headers);

        if (($first = fgets($socket)) !== "HTTP/1.1 101 Switching Protocols\r\n") {
            throw new SwitchingProtocolsException($first);
        }

        if (($headers = stream_get_line($socket, 4096, "\r\n\r\n")) === false) {
            throw new HandshakeException('receive headers failed');
        }

        $sec_key = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if (!str_contains($headers, $sec_key)) {
            if ($this->proxy) {
                throw new ConnectionException('Connection by proxy timed out:  ' . $this->endpoint, 10060);
            } else {
                throw new HandshakeException('handshake fail');
            }
        }

        stream_set_blocking($socket, false);

        $this->socket = $socket;

        if ($owner = $this->owner) {
            $owner->emit('open', $this);
        }

        return $this->socket;
    }

    protected function sendInternal(mixed $socket, string $data, ?float $timeout = null): void
    {
        $send_length = 0;
        $data_length = strlen($data);
        $end_time = microtime(true) + ($timeout ?: $this->timeout);

        $read = null;
        $except = null;

        do {
            $write = [$socket];
            if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                if (microtime(true) > $end_time) {
                    throw new TimeoutException('send timeout');
                } else {
                    continue;
                }
            }

            if (($n = fwrite($socket, $send_length === 0 ? $data : substr($data, $send_length))) === false) {
                $errno = socket_last_error($socket);
                if ($errno === 11 || $errno === 4) {
                    continue;
                }

                throw new DataTransferException('send failed');
            }

            $send_length += $n;
        } while ($send_length !== $data_length);
    }

    public function send(int $op_code, string $data, ?float $timeout = null): void
    {
        $str = chr(0x80 | $op_code);

        $data_len = strlen($data);

        $mask_bit = $this->masking ? 0x80 : 0x00;
        if ($data_len <= 125) {
            $str .= pack('C', $data_len | $mask_bit);
        } elseif ($data_len <= 65535) {
            $str .= pack('Cn', 126 | $mask_bit, $data_len);
        } else {
            $str .= pack('CJ', 127 | $mask_bit, $data_len);
        }

        if ($this->masking) {
            $key = random_bytes(4);
            $str .= $key;

            for ($i = 0; $i < $data_len; $i++) {
                $chr = $data[$i];
                $str .= chr(ord($key[$i % 4]) ^ ord($chr));
            }
        } else {
            $str .= $data;
        }

        $this->sendInternal($this->socket ?? $this->open(), $str, $timeout);
    }

    public function recv(?float $timeout = null): Message
    {
        $socket = $this->socket ?? $this->open();

        $buf = '';
        $end_time = microtime(true) + ($timeout ?: $this->timeout);

        $write = null;
        $except = null;

        while (($left = 2 - strlen($buf)) > 0) {
            $read = [$socket];
            if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                if (microtime(true) > $end_time) {
                    throw new TimeoutException('receive timeout');
                } else {
                    continue;
                }
            }

            if (($r = fread($socket, $left)) === false) {
                throw new DataTransferException('recv failed');
            }

            if ($r === '') {
                throw new ConnectionBrokenException();
            }

            $buf .= $r;
        }

        $byte1 = ord($buf[1]);

        if ($byte1 & 0x80) {
            throw new ProtocolException('Mask not support');
        }

        $len = $byte1 & 0x7F;

        if ($len <= 125) {
            $header_len = 2;
        } elseif ($len === 126) {
            $header_len = 4;
        } else {
            $header_len = 10;
        }

        $start_time = microtime(true);
        while (($left = $header_len - strlen($buf)) > 0) {
            $read = [$socket];
            if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                if (microtime(true) > $end_time) {
                    throw new TimeoutException('receive timeout');
                } else {
                    continue;
                }
            }

            if (($r = fread($socket, $left)) === false) {
                throw new DataTransferException('receive failed');
            }

            if ($r === '') {
                throw new ConnectionBrokenException();
            }

            $buf .= $r;
        }

        if ($len <= 125) {
            $payload_len = $len;
        } elseif ($len === 126) {
            $payload_len = unpack('n', substr($buf, 2, 2))[1];
        } else {
            $payload_len = unpack('J', substr($buf, 2, 8))[1];
        }

        $payload = strlen($buf) > $header_len ? substr($buf, $header_len, $payload_len) : '';

        while (($left = $payload_len - strlen($payload)) > 0) {
            $read = [$socket];
            if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                if (microtime(true) > $end_time) {
                    throw new TimeoutException('receive timeout');
                } else {
                    continue;
                }
            }

            if (($r = fread($socket, $left)) === false) {
                throw new DataTransferException('recv failed');
            }

            if ($r === '') {
                throw new ConnectionBrokenException();
            }

            if ($payload === '') {
                $payload = $r;
            } else {
                $payload .= $r;
            }
        }

        $byte0 = ord($buf[0]);

        $op_code = $byte0 & 0x0F;
        return new Message($op_code, $payload, round(microtime(true) - $start_time, 3));
    }

    public function isRecvReady(float $timeout): bool
    {
        $socket = $this->socket ?? $this->open();

        $write = null;
        $except = null;
        $end_time = microtime(true) + $timeout;

        do {
            $read = [$socket];
            if (stream_select($read, $write, $except, 0, 10000) > 0) {
                return true;
            }
        } while ($end_time > microtime(true));

        return false;
    }

    public function close(): void
    {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}