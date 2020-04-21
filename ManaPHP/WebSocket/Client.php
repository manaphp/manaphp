<?php
namespace ManaPHP\WebSocket;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\WebSocket\Client\CloseFrameException;
use ManaPHP\WebSocket\Client\ConnectionException;
use ManaPHP\WebSocket\Client\DataTransferException;
use ManaPHP\WebSocket\Client\HandshakeException;
use ManaPHP\WebSocket\Client\ProtocolException;
use ManaPHP\WebSocket\Client\SwitchingProtocolsException;
use ManaPHP\WebSocket\Client\TimeoutException;

class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @var string
     */
    protected $_proxy;

    /**
     * @var int
     */
    protected $_timeout = 3;

    /**
     * @var string
     */
    protected $_protocol;

    /**
     * @var string
     */
    protected $_origin;

    /**
     * @var string
     */
    protected $_user_agent = 'manaphp/client';

    /**
     * @var callable
     */
    protected $_on_connect;

    /**
     * @var resource
     */
    protected $_socket;

    /**
     * Client constructor.
     *
     * @param array $options
     */
    public function __construct($options)
    {
        $this->_endpoint = $options['endpoint'];

        if (isset($options['proxy'])) {
            $this->_proxy = $options['proxy'];
        }

        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
        }

        if (isset($options['protocol'])) {
            $this->_protocol = $options['protocol'];
        }

        if (isset($options['origin'])) {
            $this->_origin = $options['origin'];
        }

        if (isset($options['user_agent'])) {
            $this->_user_agent = $options['user_agent'];
        }

        if (isset($options['on_connect'])) {
            $this->_on_connect = $options['on_connect'];
        }
    }

    public function __clone()
    {
        $this->_socket = null;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->_endpoint;
    }

    /**
     * @return resource
     */
    protected function _connect()
    {
        if ($this->_socket) {
            return $this->_socket;
        }

        $parts = parse_url($this->_endpoint);
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = $parts['port'] ?? ($scheme === 'ws' ? 80 : 443);

        if ($this->_proxy) {
            $parts = parse_url($this->_proxy);

            $proxy_scheme = $parts['scheme'];
            if ($proxy_scheme !== 'http' && $proxy_scheme !== 'https') {
                throw new NotSupportedException('only support http and https proxy');
            }
            $socket = @fsockopen(($proxy_scheme === 'http' ? 'tcp' : 'ssl') . '://' . $parts['host'], $parts['port'], $errno, $errmsg, $this->_timeout);
        } else {
            $socket = @fsockopen(($scheme === 'ws' ? 'tcp' : 'ssl') . "://$host", $port, $errno, $errmsg, $this->_timeout);
        }

        if (!$socket) {
            throw new ConnectionException($errmsg . ': ' . $this->_endpoint, $errno);
        }

        stream_set_timeout($socket, (int)$this->_timeout, ($this->_timeout - (int)$this->_timeout) * 1000);
        $path = ($scheme === 'ws' ? 'http' : 'https') . substr($this->_endpoint, strpos($this->_endpoint, ':'));

        $key = bin2hex(random_bytes(16));
        $headers = "GET $path HTTP/1.1\r\n" .
            "Host: $host:$port\r\n" .
            "Sec-WebSocket-Key: $key\r\n" .
            "Connection: Upgrade\r\n" .
            "User-Agent: $this->_user_agent\r\n" .
            "Upgrade: Websocket\r\n";

        $headers .= $this->_origin ? "Origin: $this->_origin\r\n" : '';
        $headers .= $this->_protocol ? "Sec-WebSocket-Protocol: $this->_protocol\r\n" : '';

        $headers .= "Sec-WebSocket-Version: 13\r\n\r\n";

        $this->_send($socket, $headers);

        if (($first = fgets($socket)) !== "HTTP/1.1 101 Switching Protocols\r\n") {
            throw new SwitchingProtocolsException($first);
        }

        if (($headers = stream_get_line($socket, 4096, "\r\n\r\n")) === false) {
            throw new HandshakeException('receive headers failed');
        }

        $sec_key = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        if (strpos($headers, $sec_key) === false) {
            if ($this->_proxy) {
                throw new ConnectionException('Connection by proxy timed out:  ' . $this->_endpoint, 10060);
            } else {
                throw new HandshakeException('');
            }
        }

        $this->_socket = $socket;

        if ($this->_on_connect) {
            call_user_func($this->_on_connect, $this);
        }

        return $this->_socket;
    }

    /**
     * @param resource $socket
     * @param string   $data
     * @param float    $timeout
     */
    protected function _send($socket, $data, $timeout = 0.0)
    {
        $send_length = 0;
        $data_length = strlen($data);
        $start_time = microtime(true);

        do {
            if ($timeout > 0 && microtime(true) - $start_time > $timeout) {
                throw new TimeoutException('send timeout');
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
     * @param string $message
     *
     * @return void
     */
    public function sendMessage($message)
    {
        $this->fireEvent('wsClient:send', $message);

        $socket = $this->_socket ?? $this->_connect();
        $message_length = strlen($message);

        $header = chr(129);
        if ($message_length <= 125) {
            $header .= pack('C', $message_length);
        } elseif ($message_length <= 65535) {
            $header .= pack('Cn', 126, $message_length);
        } else {
            $header .= pack('CJ', 127, $message_length);
        }

        $this->_send($socket, $header . $message);
    }

    /**
     * @return bool
     */
    public function hasMessage()
    {
        $read = [$this->_socket ?? $this->_connect()];
        $write = null;
        $except = null;
        return stream_select($read, $write, $except, 0) > 0;
    }

    /**
     * @param float $timeout
     *
     * @return false|string
     */
    public function recvMessage($timeout = 0.0)
    {
        $socket = $this->_socket ?? $this->_connect();

        $buffer = '';
        $start_time = microtime(true);
        while (($left = 2 - strlen($buffer)) > 0) {
            if ($timeout > 0 && microtime(true) - $start_time > $timeout) {
                throw new TimeoutException('receive timeout');
            }

            if (($r = fread($socket, $left)) === false) {
                throw new DataTransferException('recv failed');
            }

            $buffer .= $r;
        }

        $byte1 = ord($buffer[1]);

        if ($byte1 & 0x80) {
            throw new ProtocolException('Mask not support');
        }

        $len = $byte1 & 0x7F;

        if ($len <= 125) {
            $header_length = 2;
        } elseif ($len === 126) {
            $header_length = 4;
        } else {
            $header_length = 10;
        }

        while (($left = $header_length - strlen($buffer)) > 0) {
            if ($timeout > 0 && microtime(true) - $start_time > $timeout) {
                throw new TimeoutException('receive timeout');
            }

            if (($r = fread($socket, $left)) === false) {
                throw new DataTransferException('receive failed');
            }
            $buffer .= $r;
        }

        if ($len <= 125) {
            $message_length = $len;
        } elseif ($len === 126) {
            $message_length = unpack('n', substr($buffer, 2, 2))[1];
        } else {
            $message_length = unpack('J', substr($buffer, 2, 8))[1];
        }

        $message = strlen($buffer) > $header_length ? substr($buffer, $header_length, $message_length) : '';

        while (($left = $message_length - strlen($message)) > 0) {
            if ($timeout > 0 && microtime(true) - $start_time > $timeout) {
                throw new TimeoutException('receive timeout');
            }

            if (($r = fread($socket, $left)) === false) {
                throw new DataTransferException('recv failed');
            }

            if ($message === '') {
                $message = $r;
            } else {
                $message .= $r;
            }
        }

        $byte0 = ord($buffer[0]);

        $op_code = $byte0 & 0x0F;
        if ($op_code === 0x00) {
            throw new ProtocolException('continuation frame');
        } elseif ($op_code === 0x01) {
            null;//denotes a text frame
        } elseif ($op_code === 0x02) {
            null;//denotes a binary frame
        } elseif ($op_code >= 0x03 && $op_code <= 0x07) {
            throw new ProtocolException('reserved for further non-control frames');
        } elseif ($op_code === 0x08) {
            throw new CloseFrameException('closed by peer');
        } elseif ($op_code === 0x09) {
            null;//denotes a ping
        } elseif ($op_code === 0x0A) {
            null;//denotes a pong
        } else {
            throw new ProtocolException('reserved for further control frames');
        }

        $this->fireEvent('wsClient:receive', $message);

        return $message;
    }

    public function close()
    {
        $this->_socket = null;
    }

    public function __destruct()
    {
        if ($this->_socket !== null) {
            $this->close();
        }
    }
}