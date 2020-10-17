<?php

namespace ManaPHP\Http\Client\Engine;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Http\Client\ConnectionException;
use ManaPHP\Http\Client\EngineInterface;
use ManaPHP\Http\Client\Response;
use ManaPHP\Http\Client\TimeoutException;

class Stream extends Component implements EngineInterface
{
    /**
     * @var resource
     */
    protected $_stream;

    public function __destruct()
    {
        if ($this->_stream !== null) {
            fclose($this->_stream);
            $this->_stream = null;
        }
    }

    public function __clone()
    {
        if ($this->_stream !== null) {
            fclose($this->_stream);
            $this->_stream = null;
        }
    }

    /**
     * @param \ManaPHP\Http\Client\Request $request
     *
     * @return \ManaPHP\Http\Client\Response
     */
    protected function _request_with_keepalive($request)
    {
        $host = parse_url($request->url, PHP_URL_HOST);
        $port = parse_url($request->url, PHP_URL_PORT);
        $scheme = parse_url($request->url, PHP_URL_SCHEME);

        $request->headers['Host'] = $port ? "$host:$port" : $host;
        $request->headers['Connection'] = 'keep-alive';

        if (is_array($request->body)) {
            if (isset($request->headers['Content-Type']) && str_contains($request->headers['Content-Type'], 'json')) {
                $body = json_stringify($request->body);
            } else {
                $body = http_build_query($request->body);
            }
        } else {
            $body = $request->body;
        }

        $request->headers['Content-Length'] = strlen($body);

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

        if (($stream = $this->_stream) === null) {
            if ($scheme === 'https') {
                $stream = fsockopen("ssl://$host", $port ?? 443, $errno, $errstr, $timeout);
            } else {
                $stream = fsockopen($host, $port ?? 80, $errno, $errstr, $timeout);
            }

            if ($stream === false) {
                throw new ConnectionException($errstr);
            }

            stream_set_blocking($stream, false);

            $this->_stream = $stream;
        }

        $send_length = 0;
        $data_length = strlen($data);
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

                if (($n = fwrite($stream, $send_length === 0 ? $data : substr($data, $send_length))) === false) {
                    $errno = socket_last_error($stream);
                    if ($errno === 11 || $errno === 4) {
                        continue;
                    }

                    throw new TimeoutException($request->url);
                }
                $send_length += $n;
            } while ($send_length !== $data_length);

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
                            $chunk_package_len = $pos + 2 + $len + 2;
                            if (strlen($chunked) >= $chunk_package_len) {
                                if ($len === 0) {
                                    goto READ_CHUNKED_COMPLETE;
                                }

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

                    if (($r = fread($stream, 4096)) === false) {
                        if (feof($stream)) {
                            break;
                        } else {
                            throw new TimeoutException($request->url);
                        }
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
                    fclose($this->_stream);
                    $this->_stream = null;
                }
            } else {
                fclose($this->_stream);
                $this->_stream = null;
            }
        }

        return new Response($request, $headers, $body);
    }

    /**
     * @param \ManaPHP\Http\Client\Request $request
     * @param bool                         $keepalive
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function request($request, $keepalive = false)
    {
        if ($keepalive) {
            if ($this->_stream === null) {
                return $this->_request_with_keepalive($request);
            } else {
                try {
                    return $this->_request_with_keepalive($request);
                } catch (TimeoutException $exception) {
                    fclose($this->_stream);
                    $this->_stream = null;
                    return $this->_request_with_keepalive($request);
                }
            }
        } else {
            return $this->_request_without_keepalive($request);
        }
    }

    /**
     * @param \ManaPHP\Http\Client\Request $request
     *
     * @return \ManaPHP\Http\Client\Response
     */
    protected function _request_without_keepalive($request)
    {
        $http = [];

        $http['method'] = $request->method;

        $request->headers['Connection'] = 'close';

        if (($proxy = $request->options['proxy']) !== '') {
            //if not, you will suffer "Cannot connect to HTTPS server through proxy"
            $request->options['verify_peer'] = false;

            $parts = parse_url($proxy);
            if ($parts['scheme'] !== 'http') {
                throw new NotSupportedException(['only support http type proxy: `:proxy`', 'proxy' => $proxy]);
            }

            if (isset($parts['pass'])) {
                $auth = base64_encode($parts['user'] . ':' . $parts['pass']);
                $request->headers['Proxy-Authorization'] = "Basic $auth";
            }
            $http['proxy'] = 'tcp://' . $parts['host'] . ':' . ($parts['port'] ?? '80');
        }

        $headers = [];
        foreach ($request->headers as $name => $value) {
            $headers[] = is_int($name) ? $value : "$name: $value";
        }
        $http['header'] = $headers;

        if (is_string($request->body)) {
            $http['content'] = $request->body;
        } elseif (is_array($request->body)) {
            if (isset($request->headers['Content-Type']) && str_contains($request->headers['Content-Type'], 'json')) {
                $http['content'] = json_stringify($request->body);
            } else {
                $http['content'] = http_build_query($request->body);
            }
        }

        $http['timeout'] = $request->options['timeout'];
        $http['ignore_errors'] = true;
        $http['protocol_version'] = 1.1;

        $ssl = [];
        $ssl['verify_peer'] = $request->options['verify_peer'];
        $ssl['allow_self_signed'] = $request->options['allow_self_signed'] ?? !$request->options['verify_peer'];

        if (($cafile = $request->options['cafile']) !== '') {
            $ssl['cafile'] = $this->alias->resolve($cafile);
        }

        $start_time = microtime(true);

        if (!$stream = @fopen($request->url, 'rb', false, stream_context_create(['http' => $http, 'ssl' => $ssl]))) {
            throw new ConnectionException([':last_error_message', 'url' => $request->url]);
        }

        $headers = stream_get_meta_data($stream)['wrapper_data'];

        $remote = stream_socket_get_name($stream, true);

        //strrpos compatibles with ipv6
        $request->remote_ip = ($pos = strrpos($remote, ':')) ? substr($remote, 0, $pos) : null;

        $body = stream_get_contents($stream);
        fclose($stream);

        $request->process_time = round(microtime(true) - $start_time, 3);

        return new Response($request, $headers, $body);
    }
}