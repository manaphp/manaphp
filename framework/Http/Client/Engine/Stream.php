<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client\Engine;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Http\Client\ConnectionException;
use ManaPHP\Http\Client\EngineInterface;
use ManaPHP\Http\Client\Request;
use ManaPHP\Http\Client\Response;
use ManaPHP\Http\Client\TimeoutException;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Stream extends Component implements EngineInterface
{
    protected mixed $stream;

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

    /** @noinspection PhpMixedReturnTypeCanBeReducedInspection */
    protected function connect(Request $request): mixed
    {
        $host = parse_url($request->url, PHP_URL_HOST);
        $port = parse_url($request->url, PHP_URL_PORT);
        $scheme = parse_url($request->url, PHP_URL_SCHEME);

        $request->headers['Host'] = $port ? "$host:$port" : $host;

        $timeout = $request->options['timeout'];

        if (($proxy = $request->options['proxy']) !== null) {
            $parts = parse_url($proxy);
            if ($parts['scheme'] !== 'http') {
                throw new NotSupportedException('only support http proxy');
            }

            if (isset($parts['user']) || isset($parts['pass'])) {
                throw new NotSupportedException('not support Proxy-Authorization');
            }

            $address = "tcp://$parts[host]:" . ($parts['port'] ?? 80);
            $ctx = stream_context_create();
        } else {
            if ($scheme === 'https') {
                $ssl = [];
                $ssl['verify_peer'] = $request->options['verify_peer'];
                $ssl['allow_self_signed'] = $request->options['allow_self_signed'] ??
                    !$request->options['verify_peer'];

                if (($cafile = $request->options['cafile']) !== null) {
                    $ssl['cafile'] = $this->alias->resolve($cafile);
                }

                $ctx = stream_context_create(['ssl' => $ssl]);
                $address = "ssl://$host:" . ($port ?? 443);
            } else {
                $address = "tcp://$host:" . ($port ?? 80);
                $ctx = stream_context_create();
            }
        }

        $flags = STREAM_CLIENT_CONNECT;
        if (($stream = @stream_socket_client($address, $errno, $errstr, $timeout, $flags, $ctx)) === false) {
            throw new ConnectionException($errstr);
        }

        stream_set_blocking($stream, false);

        return $this->stream = $stream;
    }

    protected function send(mixed $stream, string $url, string $data, float $end_time): void
    {
        $written = 0;

        $read = null;
        $except = null;
        do {
            $write = [$stream];
            if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                if (microtime(true) > $end_time) {
                    throw new TimeoutException($url);
                } else {
                    continue;
                }
            }

            if (($n = fwrite($stream, $written === 0 ? $data : substr($data, $written))) === false) {
                $errno = socket_last_error($stream);
                if ($errno === 11 || $errno === 4) {
                    continue;
                }

                throw new TimeoutException($url);
            }
            $written += $n;
        } while ($written !== strlen($data));
    }

    protected function buildHeader(Request $request): string
    {
        $data = strtoupper($request->method) . ' ' . $request->url . " HTTP/1.1\r\n";
        foreach ($request->headers as $name => $value) {
            $data .= is_int($name) ? "$value\r\n" : "$name: $value\r\n";
        }

        return $data;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    protected function recvHeader(mixed $stream, string $url, float $end_time): array
    {
        $recv = '';
        $write = null;
        $headers_end = null;
        while (true) {
            $read = [$stream];
            if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                if (microtime(true) > $end_time) {
                    throw new TimeoutException($url);
                } else {
                    continue;
                }
            }

            if (($r = fread($stream, 4096)) === false) {
                throw new TimeoutException($url);
            }

            $recv .= $r;

            if (($headers_end = strpos($recv, "\r\n\r\n")) !== false) {
                break;
            }
        }

        $headers = explode("\r\n", substr($recv, 0, $headers_end));
        $body = substr($recv, $headers_end + 4);

        return [$headers, $body];
    }

    protected function recvChunkedBody(mixed $stream, string $url, string $body, float $end_time): string
    {
        $chunked = $body;
        $body = '';

        $write = null;
        $except = null;
        while (true) {
            $next_read_len = 1;
            while (true) {
                if (($pos = strpos($chunked, "\r\n")) !== false) {
                    $len = (int)base_convert(substr($chunked, 0, $pos), 16, 10);

                    if ($len === 0) {
                        return $body;
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
                    throw new TimeoutException($url);
                } else {
                    continue;
                }
            }

            if (($r = fread($stream, $next_read_len)) === false) {
                if (feof($stream)) {
                    break;
                } else {
                    throw new TimeoutException($url);
                }
            }
            $chunked .= $r;
        }

        return '';
    }

    protected function recvContentLengthBody(mixed $stream, string $url, string $body, int $length, float $end_time
    ): string {
        $write = null;
        $except = null;
        while ($length !== strlen($body)) {
            $read = [$stream];
            if (stream_select($read, $write, $except, 0, 10000) <= 0) {
                if (microtime(true) > $end_time) {
                    throw new TimeoutException($url);
                } else {
                    continue;
                }
            }

            if (feof($stream)) {
                break;
            }

            if (($r = fread($stream, 4096)) === false) {
                throw new TimeoutException($url);
            }

            $body .= $r;
        }

        return $body;
    }

    public function request(Request $request, ?string $body): Response
    {
        $request->headers['Accept-Encoding'] ??= 'gzip, deflate';
        $request->headers['Connection'] ??= 'keep-alive';

        $start = strpos($request->url, '://') + 3;
        $end = strpos($request->url, '/', $start);
        $request->headers['Host'] = substr($request->url, $start, $end - $start);

        $start_time = microtime(true);
        $timeout = $request->options['timeout'];
        $end_time = $start_time + $timeout;

        $header = $this->buildHeader($request);

        if (($stream = $this->stream) === null) {
            $stream = $this->connect($request);
            $this->send($stream, $request->url, $header, $end_time);
        } else {
            try {
                $this->send($stream, $request->url, $header, $end_time);
            } finally {
                fclose($stream);
                $this->stream = null;

                $stream = $this->connect($request);
                $this->send($stream, $request->url, $header, $end_time);
            }
        }

        $this->send($stream, $request->url, $body === '' ? "\r\n" : "\r\n$body", $end_time);
        $headers = null;

        try {
            $success = false;

            list($headers, $body) = $this->recvHeader($stream, $request->url, $end_time);

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
                $body = $this->recvChunkedBody($stream, $request->url, $body, $end_time);
            } else {
                $body = $this->recvContentLengthBody($stream, $request->url, $body, $content_length, $end_time);
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

                if (strtolower($connection_value) !== 'keep-alive') {
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
}