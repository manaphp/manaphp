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
class Curl extends Component implements EngineInterface
{
    /**
     * @var resource
     */
    protected $curl;

    public function __destruct()
    {
        if ($this->curl !== null) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    public function __clone()
    {
        if ($this->curl !== null) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    /**
     * @param \ManaPHP\Http\Client\Request $request
     * @param string                       $body
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function request($request, $body)
    {
        $content = '';
        $header_length = 0;

        if (($curl = $this->curl) === null) {
            $curl = curl_init();
            $this->curl = $curl;
        }

        try {
            $success = false;

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_AUTOREFERER, true);

            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 8);

            switch ($request->method) {
                case 'GET':
                    break;
                case 'POST':
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                    break;
                case 'PATCH':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                    break;
                case 'PUT':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                    break;
                case 'DELETE':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
                case 'HEAD':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
                    curl_setopt($curl, CURLOPT_NOBODY, true);
                    break;
                case 'OPTIONS':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
                    break;
                default:
                    throw new NotSupportedException(['`:method` method is not support', 'method' => $request->method]);
            }

            $timeout = $request->options['timeout'];
            curl_setopt($curl, CURLOPT_URL, $request->url);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_HEADER, 1);

            if (($proxy = $request->options['proxy']) !== '') {
                $parts = parse_url($proxy);
                $scheme = $parts['scheme'];
                if ($scheme === 'http') {
                    curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                } elseif ($scheme === 'sock4') {
                    curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                } elseif ($scheme === 'sock5') {
                    curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                } else {
                    throw new NotSupportedException(['`%s` scheme of `%s` proxy is unknown', $scheme, $proxy]);
                }

                curl_setopt($curl, CURLOPT_PROXYPORT, $parts['port']);
                curl_setopt($curl, CURLOPT_PROXY, $parts['host']);
                if (isset($parts['user'], $parts['pass'])) {
                    curl_setopt($curl, CURLOPT_PROXYUSERNAME, $parts['user']);
                    curl_setopt($curl, CURLOPT_PROXYPASSWORD, $parts['pass']);
                }
            }

            if (($cafile = $request->options['cafile']) !== '') {
                curl_setopt($curl, CURLOPT_CAINFO, $this->alias->resolve($cafile));
            } elseif (DIRECTORY_SEPARATOR === '\\') {
                $request->options['verify_peer'] = false;
            }

            if (!$request->options['verify_peer']) {
                /** @noinspection CurlSslServerSpoofingInspection */
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                /** @noinspection CurlSslServerSpoofingInspection */
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            }

            $headers = [];
            foreach ($request->headers as $name => $value) {
                $headers[] = is_int($name) ? $value : "$name: $value";
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $start_time = microtime(true);

            $content = curl_exec($curl);

            $errno = curl_errno($curl);
            if ($errno === 23 || $errno === 61) {
                curl_setopt($curl, CURLOPT_ENCODING, 'none');
                $content = curl_exec($curl);
                $errno = curl_errno($curl);
            }

            if ($errno) {
                throw new ConnectionException(['connect failed: `%s` %s', $request->url, curl_error($curl)]);
            }

            $header_length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $request->remote_ip = curl_getinfo($curl, CURLINFO_PRIMARY_IP);
            $request->process_time = round(microtime(true) - $start_time, 3);

            $success = true;
        } finally {
            if (!$success) {
                curl_close($curl);
            }
        }

        $response_headers = explode("\r\n", substr($content, 0, $header_length - 4));
        $response_body = substr($content, $header_length);

        return new Response($request, $response_headers, $response_body);
    }
}