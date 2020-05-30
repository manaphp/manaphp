<?php

namespace ManaPHP\Http\Client\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Http\Client;
use ManaPHP\Http\Client\BadResponseException;
use ManaPHP\Http\Client\ConnectionException;

class Stream extends Client
{
    /**
     * @param array $headers
     *
     * @return array
     */
    protected function _getResponseHeaders($headers)
    {
        if (preg_match('#\s(?:301|302)\s#', $headers[0], $match) !== 1) {
            return $headers;
        }

        for ($i = count($headers) - 1; $i >= 0; $i--) {
            $header = $headers[$i];
            if (strpos($header, 'HTTP/') === 0) {
                return $i === 0 ? $headers : array_slice($headers, $i);
            }
        }

        return $headers;
    }

    /**
     * @param \ManaPHP\Http\Client\Request $request
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function do_request($request)
    {
        $http = [];

        $http['method'] = $request->method;

        $headers = [];
        foreach ($request->headers as $k => $v) {
            $headers[] = is_string($k) ? "$k: $v" : $v;
        }

        if (isset($request->options['proxy'])) {
            //if you suffer "Cannot connect to HTTPS server through proxy", please set `verify_peer` to false
            $parts = parse_url($request->options['proxy']);
            if ($parts['scheme'] !== 'http') {
                throw new NotSupportedException(['only support http type proxy: `:proxy`', 'proxy' => $request->options['proxy']]);
            }

            if (isset($parts['pass'])) {
                $headers[] = 'Proxy-Authorization: Basic ' . base64_encode($parts['user'] . ':' . $parts['pass']);
            }
            $http['proxy'] = 'tcp://' . $parts['host'] . ':' . ($parts['port'] ?? '80');
        }

        $http['header'] = $headers;

        if (is_string($request->body)) {
            $http['content'] = $request->body;
        } elseif (is_array($request->body)) {
            if (isset($options['Content-Type']) && str_contains($request->headers['Content-Type'], 'json')) {
                $http['content'] = json_stringify($request->body);
            } else {
                $http['content'] = http_build_query($request->body);
            }
        }

        $http['timeout'] = $request->options['timeout'];
        $http['ignore_errors'] = true;

        $ssl = [];
        $ssl['verify_peer'] = $request->options['verify_peer'];
        $ssl['allow_self_signed'] = $request->options['allow_self_signed'] ?? !$request->options['verify_peer'];

        if (isset($request->options['cafile'])) {
            $ssl['cafile'] = $this->alias->resolve($request->options['cafile']);
        }

        $start_time = microtime(true);

        if (!$stream = @fopen($request->url, 'rb', false, stream_context_create(['http' => $http, 'ssl' => $ssl]))) {
            throw new ConnectionException(['`:url`: `:last_error_message`', 'url' => $request->url]);
        }
        $meta = stream_get_meta_data($stream);

        $remote = stream_socket_get_name($stream, true);
        $remote_ip = ($pos = strrpos($remote, ':')) ? substr($remote, 0, $pos) : null;//strrpos compatibles with ipv6

        $body = stream_get_contents($stream);
        fclose($stream);

        $process_time = round(microtime(true) - $start_time, 3);

        $headers = $this->_getResponseHeaders($meta['wrapper_data']);

        $content_type = null;
        foreach ($headers as $header) {
            if (strpos($header, 'Content-Type:') === 0) {
                $content_type = trim(substr($header, 13));
                break;
            }
        }

        $http_code = null;
        if ($headers && preg_match('#\d{3}#', $headers[0], $match)) {
            $http_code = (int)$match[0];
        }

        if (is_string($body)) {
            if (in_array('Content-Encoding: gzip', $headers, true)) {
                if (($decoded = @gzdecode($body)) === false) {
                    throw new BadResponseException(['`:url`: `:ungzip failed`', 'url' => $request->url]);
                } else {
                    $body = $decoded;
                }
            } elseif (in_array('Content-Encoding: deflate', $headers, true)) {
                if (($decoded = @gzinflate($body)) === false) {
                    throw new BadResponseException(['`:url`: deflate failed', 'url' => $request->url]);
                } else {
                    $body = $decoded;
                }
            }
        }

        $response = $this->_di->get('ManaPHP\Http\Client\Response');
        $response->url = $request->url;
        $response->remote_ip = $remote_ip;
        $response->http_code = $http_code;
        $response->headers = $headers;
        $response->process_time = $process_time;
        $response->content_type = $content_type;
        $response->body = $body;

        return $response;
    }
}