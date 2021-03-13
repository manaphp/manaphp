<?php

namespace ManaPHP\Http\Client\Engine;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Http\Client\ConnectionException;
use ManaPHP\Http\Client\EngineInterface;
use ManaPHP\Http\Client\Response;
use ManaPHP\Http\Client\TimeoutException;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Stream extends Component implements EngineInterface
{
    /**
     * @var resource
     */
    protected $stream;

    public function __destruct()
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    public function __clone()
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * @param \ManaPHP\Http\Client\Request $request
     * @param string                       $body
     *
     * @return \ManaPHP\Http\Client\Response
     */
    protected function requestInternal($request, $body)
    {
        $host = parse_url($request->url, PHP_URL_HOST);
        $port = parse_url($request->url, PHP_URL_PORT);
        $scheme = parse_url($request->url, PHP_URL_SCHEME);

        $request->headers['Host'] = $port ? "$host:$port" : $host;

        if (!isset($request->headers['Connection'])) {
            $request->headers['Connection'] = 'keep-alive';
        }

        $data = strtoupper($request->method) . ' ' . $request->url . " HTTP/1.1\r\n";
        foreach ($request->headers as $name => $value) {
            $data .= is_int($name) ? "$value\r\n" : "$name: $value\r\n";
        }

        $data .= "\r\n";

        if ($body !== '') {
            $data .= $body;
        }

        $start_time = microtime(true);
        $timeout = $request->options['timeout'];
        $end_time = $start_time + $timeout;

        if (($stream = $this->stream) === null) {
            $flags = STREAM_CLIENT_CONNECT;

            if (($proxy = $request->options['proxy']) !== '') {
                $parts = parse_url($proxy);
                if ($parts['scheme'] !== 'http') {
                    throw new NotSupportedException('only support http proxy');
                }

                if (isset($parts['user']) || isset($parts['pass'])) {
                    throw new NotSupportedException('not support Proxy-Authorization');
                }

                $host = $parts['host'];
                $port = $parts['port'] ?? 80;
                $stream = stream_socket_client("tcp://$host:$port", $errno, $errstr, $timeout, $flags);
            } else {
                if ($scheme === 'https') {
                    $ssl = [];
                    $ssl['verify_peer'] = $request->options['verify_peer'];
                    $ssl['allow_self_signed'] = $request->options['allow_self_signed'] ??
                        !$request->options['verify_peer'];

                    if (($cafile = $request->options['cafile']) !== '') {
                        $ssl['cafile'] = $this->alias->resolve($cafile);
                    }
                    $port = $port ?? 443;
                    $ctx = stream_context_create(['ssl' => $ssl]);
                    $stream = stream_socket_client("ssl://$host:$port", $errno, $errstr, $timeout, $flags, $ctx);
                } else {
                    $port = $port ?? 80;
                    $stream = stream_socket_client("tcp://$host:$port", $errno, $errstr, $timeout, $flags);
                }
            }

            if ($stream === false) {
                throw new ConnectionException($errstr);
            }

            stream_set_blocking($stream, false);

            $this->stream = $stream;
        }

        $written = 0;
        $headers = null;

        try {
            $success = false;

            $read = null;
            $except = null;
            do {
                $write = [$stream];
                if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                    if (microtime(true) > $end_time) {
                        throw new TimeoutException($request->url);
                    } else {
                        continue;
                    }
                }

                if (($n = fwrite($stream, $written === 0 ? $data : substr($data, $written))) === false) {
                    $errno = socket_last_error($stream);
                    if ($errno === 11 || $errno === 4) {
                        continue;
                    }

                    throw new TimeoutException($request->url);
                }
                $written += $n;
            } while ($written !== strlen($data));

            $recv = '';
            $write = null;
            $headers_end = null;
            while (true) {
                $read = [$stream];
                if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                    if (microtime(true) > $end_time) {
                        throw new TimeoutException($request->url);
                    } else {
                        continue;
                    }
                }

                if (($r = fread($stream, 4096)) === false) {
                    throw new TimeoutException($request->url);
                }

                $recv .= $r;

                if (($headers_end = strpos($recv, "\r\n\r\n")) !== false) {
                    break;
                }
            }

            $headers = explode("\r\n", substr($recv, 0, $headers_end));
            $body = substr($recv, $headers_end + 4);

            $content_length = null;
            $transfer_encoding = null;
            foreach ($headers as $header) {
                if (stripos($header, 'Content-Length:') === 0) {
                    $content_length = (int)trim(substr($header, 15));
                } elseif (stripos($header, 'Transfer-Encoding:') === 0) {
                    $transfer_encoding = trim(substr($header, 18));
                }
            }

            if ($transfer_encoding === 'chunked') {
                $chunked = $body;
                $body = '';

                $write = null;

                while (true) {
                    $next_read_len = 1;
                    while (true) {
                        if (($pos = strpos($chunked, "\r\n")) !== false) {
                            $len = (int)base_convert(substr($chunked, 0, $pos), 16, 10);
                            
                            if ($len === 0) {
                                goto READ_CHUNKED_COMPLETE;
                            }

                            $chunk_package_len = $pos + 2 + $len + 2;
                            if (strlen($chunked) >= $chunk_package_len) {
                                $body .= substr($chunked, $pos + 2, $len);
                                $chunked = substr($chunked, $chunk_package_len);
                            } else {
                                $next_read_len = $chunk_package_len - strlen($chunked);
                                break;
                            }
                        } else {
                            break;
                        }
                    }

                    $read = [$stream];
                    if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                        if (microtime(true) > $end_time) {
                            throw new TimeoutException($request->url);
                        } else {
                            continue;
                        }
                    }

                    if (($r = fread($stream, $next_read_len)) === false) {
                        if (feof($stream)) {
                            break;
                        } else {
                            throw new TimeoutException($request->url);
                        }
                    }
                    $chunked .= $r;
                }

                READ_CHUNKED_COMPLETE:
            } else {
                while ($content_length !== strlen($body)) {
                    $read = [$stream];
                    if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                        if (microtime(true) > $end_time) {
                            throw new TimeoutException($request->url);
                        } else {
                            continue;
                        }
                    }

                    if (feof($stream)) {
                        break;
                    }

                    if (($r = fread($stream, 4096)) === false) {
                        throw new TimeoutException($request->url);
                    }

                    $body .= $r;
                }
            }

            $request->process_time = round(microtime(true) - $start_time, 3);

            $remote = stream_socket_get_name($stream, true);

            //strrpos compatibles with ipv6
            $request->remote_ip = ($pos = strrpos($remote, ':')) ? substr($remote, 0, $pos) : null;

            $success = true;
        } finally {
            if ($success) {
                $connection_value = null;
                foreach ($headers as $header) {
                    if (stripos($header, 'Connection:') === 0) {
                        $connection_value = trim(substr($header, 11));
                        break;
                    }
                }

                if ($connection_value !== 'keep-alive') {
                    fclose($this->stream);
                    $this->stream = null;
                }
            } else {
                fclose($this->stream);
                $this->stream = null;
            }
        }

        return new Response($request, $headers, $body);
    }

    /**
     * @param \ManaPHP\Http\Client\Request $request
     * @param string                       $body
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function request($request, $body)
    {
        if (!isset($request->headers['Accept-Encoding'])) {
            $request->headers['Accept-Encoding'] = 'gzip, deflate';
        }

        if ($this->stream === null) {
            return $this->self->requestInternal($request, $body);
        } else {
            try {
                return $this->self->requestInternal($request, $body);
            } catch (TimeoutException $exception) {
                fclose($this->stream);
                $this->stream = null;
                return $this->self->requestInternal($request, $body);
            }
        }
    }
}