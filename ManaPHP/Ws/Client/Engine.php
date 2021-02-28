<?php

namespace ManaPHP\Ws\Client;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;

class Engine extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $proxy;

    /**
     * @var float
     */
    protected $timeout = 3.0;

    /**
     * @var string
     */
    protected $protocol;

    /**
     * @var bool
     */
    protected $masking = true;

    /**
     * @var string
     */
    protected $origin;

    /**
     * @var string
     */
    protected $user_agent = 'manaphp/client';

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var \ManaPHP\Ws\Client
     */
    protected $owner;

    /**
     * @param array $options
     */
    public function __construct($options)
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
        $this->self->close();
    }

    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint)
    {
        if ($this->socket !== null) {
            $this->self->close();
        }

        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @return resource
     */
    protected function open()
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

        $this->self->sendInternal($socket, $headers);

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

    /**
     * @param resource $socket
     * @param string   $data
     * @param float    $timeout
     *
     * @return void
     */
    protected function sendInternal($socket, $data, $timeout = null)
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

    /**
     * @param int    $op_code
     * @param string $data
     * @param float  $timeout
     */
    public function send($op_code, $data, $timeout = null)
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

        $this->self->sendInternal($this->socket ?? $this->self->open(), $str, $timeout);
    }

    /**
     * @param float $timeout
     *
     * @return \ManaPHP\Ws\Client\Message
     */
    public function recv($timeout = null)
    {
        $socket = $this->socket ?? $this->self->open();

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

    /**
     * @param float $timeout
     *
     * @return bool
     */
    public function isRecvReady($timeout)
    {
        $socket = $this->socket ?? $this->self->open();

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

    /**
     * @return void
     */
    public function close()
    {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->self->close();
    }
}