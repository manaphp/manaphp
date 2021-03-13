<?php

namespace ManaPHP\Http\Client\Engine;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Http\Client\ConnectionException;
use ManaPHP\Http\Client\EngineInterface;
use ManaPHP\Http\Client\Response;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Fopen extends Component implements EngineInterface
{
    /**
     * @param \ManaPHP\Http\Client\Request $request
     * @param string                       $body
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function request($request, $body)
    {
        $http = [];

        $http['method'] = $request->method;

        if (!isset($request->headers['Accept-Encoding'])) {
            $request->headers['Accept-Encoding'] = 'gzip, deflate';
        }

        if (!isset($request->headers['Connection'])) {
            $request->headers['Connection'] = 'close';
        }

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

        if (is_string($body)) {
            $http['content'] = $body;
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
            $error = error_get_last()['message'] ?? '';
            throw new ConnectionException(['connect to `%s` failed: %s', $request->url, $error]);
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