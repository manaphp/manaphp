<?php
namespace ManaPHP\WebSocket;

use ManaPHP\Component;
use ManaPHP\WebSocket\Client\DataTransferException;
use ManaPHP\WebSocket\Client\HandshakeException;
use ManaPHP\WebSocket\Client\ProtocolException;
use ManaPHP\WebSocket\Client\TimeoutException;

class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @var resource
     */
    protected $_socket;

    /**
     * @var string
     */
    protected $_buffer = '';

    /**
     * Client constructor.
     *
     * @param string|array $options
     */
    public function __construct($options)
    {
        if (is_string($options)) {
            $this->_endpoint = $options;
        } elseif (is_array($options)) {
            $this->_endpoint = $options['endpoint'];
        }
    }

    public function __clone()
    {
        $this->_socket = null;
        $this->_buffer = null;
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
        if (($pos = strpos($this->_endpoint, '/', $scheme === 'ws' ? 5 : 6)) === false) {
            $path = '/';
        } else {
            $path = substr($this->_endpoint, $pos);
        }

        $key = bin2hex(random_bytes(16));
        $socket = fsockopen(($scheme === 'ws' ? 'tcp' : 'ssl') . "://$host", $port);
        $headers = "GET $path HTTP/1.1\r\n" .
            "Origin: null\r\n" .
            "Host: $host:$port\r\n" .
            "Sec-WebSocket-Key: $key\r\n" .
            "User-Agent: manaphp/client\r\n" .
            "Upgrade: Websocket\r\n" .
            "Sec-WebSocket-Protocol: jsonrpc\r\n" .
            "Sec-WebSocket-Version: 13\r\n\r\n";

        $this->_send($socket, $headers);

        $buffer = '';

        $sec_key = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        while (true) {
            if (($recv = fread($socket, 4096)) === false) {
                throw new DataTransferException('send failed');
            }
            $buffer .= $recv;

            if (($pos = strpos($buffer, "\r\n\r\n")) !== false) {
                $headers = substr($buffer, 0, $pos);
                if (strpos($headers, $sec_key) === false) {
                    throw new HandshakeException('');
                }

                if ($pos + 4 !== strlen($buffer)) {
                    $this->_buffer = substr($buffer, $pos + 4);
                    if (ord($this->_buffer[0]) & 0x0F === 0x08) {
                        throw new HandshakeException('');
                    }
                }
                break;
            }
        }
        return $this->_socket = $socket;
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
        $this->logger->debug($message, 'wsClient.sendMessage');

        $socket = $this->_socket ?? $this->_connect();
        $message_length = strlen($message);

        $header = chr(130);
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
        return $this->_buffer !== '';
    }

    /**
     * @param float $timeout
     *
     * @return false|string
     */
    public function recvMessage($timeout = 0.0)
    {
        $socket = $this->_socket ?? $this->_connect();

        $buffer = $this->_buffer;
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

        $byte0 = ord($buffer[0]);

        if ($byte0 & 0x0F !== 0x02) {
            throw new ProtocolException('only support binary frame');
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

        $this->_buffer = strlen($buffer) - ($header_length + $message_length) > 0 ? substr($buffer, $header_length + $message_length) : '';

        $this->logger->debug($message, 'wsClient.receiveMessage');

        return $message;
    }

    public function close()
    {
        $this->_socket = null;
        $this->_buffer = '';
    }

    public function __destruct()
    {
        if ($this->_socket !== null) {
            $this->close();
        }
    }
}