<?php
namespace ManaPHP\Http\Client\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Http\Client;
use ManaPHP\Http\Client\ConnectionException;
use ManaPHP\Http\Client\Response;

class Stream extends Client
{
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
            if (isset($options['Content-Type']) && strpos($request->headers['Content-Type'], 'json') !== false) {
                $http['content'] = json_encode($request->body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
        $body = stream_get_contents($stream);
        fclose($stream);

        $process_time = round(microtime(true) - $start_time, 3);

        $headers = $meta['wrapper_data'];

        for ($i = count($headers) - 1; $i >= 0; $i--) {
            $header = $headers[$i];
            /** @noinspection NotOptimalIfConditionsInspection */
            if (strpos($header, ':') === false && $headers !== '') {
                break;
            }
        }

        if ($i !== 0) {
            $headers = array_slice($headers, $i);
        }

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

        $response = new Response();
        $response->url = $request->url;
        $response->remote_ip = null;
        $response->http_code = $http_code;
        $response->headers = $headers;
        $response->process_time = 0;
        $response->content_type = $content_type;
        $response->body = $body;
        $response->process_time = $process_time;

        return $response;
    }
}