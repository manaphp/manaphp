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

        $request->headers['Connection'] = 'close';

        if (($proxy = $request->options['proxy']) !== '') {
            //if not, you will suffer "Cannot connect to HTTPS server through proxy"
            $request->options['verify_peer'] = false;

            $parts = parse_url($proxy);
            if ($parts['scheme'] !== 'http') {
                throw new NotSupportedException(['only support http type proxy: `:proxy`', 'proxy' => $proxy]);
            }

            if (isset($parts['pass'])) {
                $request->headers['Proxy-Authorization'] = 'Basic ' . base64_encode($parts['user'] . ':' . $parts['pass']);
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
        $request->remote_ip = ($pos = strrpos($remote, ':')) ? substr($remote, 0, $pos) : null;//strrpos compatibles with ipv6

        $body = stream_get_contents($stream);
        fclose($stream);

        $request->process_time = round(microtime(true) - $start_time, 3);

        return new Response($request, $headers, $body);
    }
}