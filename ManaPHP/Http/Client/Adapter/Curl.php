<?php

namespace ManaPHP\Http\Client\Adapter;


use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\Client;
use ManaPHP\Http\Client\ConnectionException;

class Curl extends Client
{
    /**
     * @param \ManaPHP\Http\Client\Request $request
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function do_request($request)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 8);

        $body = $request->body;
        if (is_array($body)) {
            if (isset($headers['Content-Type']) && strpos($request->headers['Content-Type'], 'json') !== false) {
                $body = json_stringify($body);
            } else {
                $hasFiles = false;
                foreach ($body as $k => $v) {
                    if (is_string($v) && strlen($v) > 1 && $v[0] === '@' && LocalFS::fileExists($v)) {
                        $hasFiles = true;
                        $file = $this->alias->resolve($v);
                        $body[$k] = curl_file_create($file, mime_content_type($file) ?: null, basename($file));
                    } elseif (is_object($v)) {
                        $hasFiles = true;
                    }
                }

                if (!$hasFiles) {
                    $body = http_build_query($body);
                }
            }
        }

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
                break;
        }

        $timeout = $request->options['timeout'];
        curl_setopt($curl, CURLOPT_URL, $request->url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($this->_proxy) {
            $parts = parse_url($this->_proxy);
            $scheme = $parts['scheme'];
            if ($scheme === 'http') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            } elseif ($scheme === 'sock4') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
            } elseif ($scheme === 'sock5') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            } else {
                throw new NotSupportedException(['`:scheme` scheme of `:proxy` proxy is unknown', 'scheme' => $scheme, 'proxy' => $this->_proxy]);
            }

            curl_setopt($curl, CURLOPT_PROXYPORT, $parts['port']);
            curl_setopt($curl, CURLOPT_PROXY, $parts['host']);
            if (isset($parts['user'], $parts['pass'])) {
                curl_setopt($curl, CURLOPT_PROXYUSERNAME, $parts['user']);
                curl_setopt($curl, CURLOPT_PROXYPASSWORD, $parts['pass']);
            }
        }

        if (isset($request->options['cafile'])) {
            curl_setopt($curl, CURLOPT_CAINFO, $this->alias->resolve($request->options['cafile']));
        }

        if (!$request->options['verify_peer']) {
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        $headers = [];
        foreach ($request->headers as $name => $value) {
            $headers[] = "$name: $value";
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $start_time = microtime(true);

        $content = curl_exec($curl);

        $err = curl_errno($curl);
        if ($err === 23 || $err === 61) {
            curl_setopt($curl, CURLOPT_ENCODING, 'none');
            $content = curl_exec($curl);
        }

        /** @noinspection NotOptimalIfConditionsInspection */
        if (($errno = curl_errno($curl)) === CURLE_SSL_CACERT && !isset($options['cafile']) && DIRECTORY_SEPARATOR === '\\') {
            $this->logger->warn('ca.pem file is not exists, you should download from https://curl.haxx.se/ca/cacert.pem', 'httpClient.noCaCert');
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $content = curl_exec($curl);
            $errno = curl_error($curl);
        }

        $process_time = round(microtime(true) - $start_time, 3);

        if ($errno) {
            throw new ConnectionException(['connect failed: `:url` :message', 'url' => $request->url, 'message' => curl_error($curl)]);
        }

        $header_length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        $response = $this->_di->get('ManaPHP\Http\Client\Response');

        $response->url = $request->url;
        $response->remote_ip = curl_getinfo($curl, CURLINFO_PRIMARY_IP);
        $response->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response->headers = explode("\r\n", substr($content, 0, $header_length - 4));
        $response->process_time = $process_time;
        $response->content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $response->body = substr($content, $header_length);
        $response->stats = [
            'total_time' => curl_getinfo($curl, CURLINFO_TOTAL_TIME),
            'namelookup_time' => curl_getinfo($curl, CURLINFO_NAMELOOKUP_TIME),
            'connect_time' => curl_getinfo($curl, CURLINFO_CONNECT_TIME),
            'pretransfer_time' => curl_getinfo($curl, CURLINFO_PRETRANSFER_TIME),
            'starttransfer_time' => curl_getinfo($curl, CURLINFO_STARTTRANSFER_TIME)];

        curl_close($curl);

        return $response;
    }
}