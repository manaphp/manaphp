<?php
namespace ManaPHP\Curl;

use ManaPHP\Component;
use ManaPHP\Curl\Easy\Response;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Exception\NotSupportedException;

/**
 * Class ManaPHP\Curl\Easy
 *
 * @package Curl
 */
class Easy extends Component implements EasyInterface
{
    const USER_AGENT_IE = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';

    /**
     * @var array
     */
    protected $_headers = [];

    /**
     * @var array
     */
    protected $_options = [];

    /**
     * @var bool
     */
    protected $_peek = false;

    /**
     * @var string
     */
    protected $_proxy = '';

    /**
     * @var string
     */
    protected $_caFile = '@manaphp/Curl/https/ca.pem';

    /**
     * @var int
     */
    protected $_timeout = 10;

    /**
     * @var bool
     */
    protected $_sslVerify = true;

    /**
     * @var string
     */
    protected $_userAgent = self::USER_AGENT_IE;

    /**
     * Client constructor.
     *
     * @param array $options
     * @param array $headers
     *
     * - `User-Agent`: User Agent to send to the server
     *   (string, default: php-requests/$version)
     */
    public function __construct($options = [], $headers = [])
    {
        if (!function_exists('curl_init')) {
            throw new ExtensionNotInstalledException('curl');
        }

        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
            unset($options['timeout']);
        }
        if (isset($options['sslVerify'])) {
            $this->_sslVerify = (bool)$options['sslVerify'];
            unset($options['sslVerify']);
        }

        if (isset($options['proxy'])) {
            $this->_proxy = $options['proxy'];
            unset($options['proxy']);
        }

        if (isset($options['userAgent'])) {
            $this->_userAgent = $options['userAgent'];
            unset($options['userAgent']);
        }

        $this->_options = $options;

        $this->_headers = $headers;
    }

    /**
     * @param string $proxy
     * @param bool   $peek
     *
     * @return static
     */
    public function setProxy($proxy = '127.0.0.1:8888', $peek = true)
    {
        if (strpos($proxy, '://') === false) {
            $this->_proxy = 'http://' . $proxy;
        } else {
            $this->_proxy = $proxy;
        }

        $this->_peek = $peek;

        return $this;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function setCaFile($file)
    {
        $this->_caFile = $file;

        return $this;
    }

    /**
     * @param int $seconds
     *
     * @return static
     */
    public function setTimeout($seconds)
    {
        $this->_timeout = $seconds;

        return $this;
    }

    /**
     * @param bool $verify
     *
     * @return static
     */
    public function setSslVerify($verify)
    {
        $this->_sslVerify = $verify;

        return $this;
    }

    /**
     * @param string       $type
     * @param string|array $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    protected function request($type, $url, $body, $headers, $options)
    {
        if (is_array($url)) {
            if (count($url) > 1) {
                $uri = $url[0];
                unset($url[0]);
                $url = $uri . (strpos($uri, '?') !== false ? '&' : '?') . http_build_query($url);
            } else {
                $url = $url[0];
            }
        }

        if (preg_match('/^http(s)?:\/\//i', $url) !== 1) {
            throw new NotSupportedException(['only HTTP requests can be handled: `:url`'/**m06c8af26e23f01884*/, 'url' => $url]);
        }

        /** @noinspection AdditionOperationOnArraysInspection */
        return $this->_request($type, $url, $body, $headers + $this->_headers, $options + $this->_options);
    }

    /**
     * @param string       $type
     * @param string       $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function _request($type, $url, $body, $headers, $options)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 8);

        if (isset($headers['Cookie'])) {
            curl_setopt($curl, CURLOPT_COOKIE, $headers['Cookie']);
        }

        if (is_array($body)) {
            $hasFiles = false;
            /** @noinspection ForeachSourceInspection */
            foreach ($body as $k => $v) {
                if (is_string($v) && strlen($v) > 1 && $v[0] === '@' && is_file(substr($v, 1))) {
                    $hasFiles = true;
                    if (class_exists('CURLFile')) {
                        $file = substr($v, 1);

                        $parts = explode(';', $file);

                        if (count($parts) === 1) {
                            $body[$k] = new \CURLFile($file);
                        } else {
                            $file = $parts[0];
                            $types = explode('=', $parts[1]);
                            if ($types[0] !== 'type' || count($types) !== 2) {
                                throw new NotSupportedException(['`:file` file name format is invalid', 'file' => $v]);
                            } else {
                                $body[$k] = new \CURLFile($file, $types[1]);
                            }
                        }
                    }
                } elseif (is_object($v)) {
                    $hasFiles = true;
                }
            }

            if (!$hasFiles) {
                $body = http_build_query($body);
            }
        }

        switch ($type) {
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
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, isset($options['timeout']) ? $options['timeout'] : $this->_timeout);
        curl_setopt($curl, CURLOPT_REFERER, isset($headers['Referer']) ? $headers['Referer'] : $url);
        curl_setopt($curl, CURLOPT_USERAGENT, isset($headers['User-Agent']) ? $headers['User-Agent'] : $this->_userAgent);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        unset($headers['Referer'], $headers['User-Agent'], $headers['Cookie']);

        $formatted_headers = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $formatted_headers[] = $v;
            } else {
                $formatted_headers[] = $k . ': ' . $v;
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $formatted_headers);

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

        if ($this->_caFile) {
            curl_setopt($curl, CURLOPT_CAINFO, $this->alias->resolve($this->_caFile));
        }

        if (!$this->_sslVerify || $this->_peek) {
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        foreach ($options as $k => $v) {
            if (is_int($k)) {
                curl_setopt($curl, $k, $v);
            }
        }

        $start_time = microtime(true);

        $content = curl_exec($curl);

        $err = curl_errno($curl);
        if ($err === 23 || $err === 61) {
            curl_setopt($curl, CURLOPT_ENCODING, 'none');
            $content = curl_exec($curl);
        }

        if (curl_errno($curl)) {
            throw new ConnectionException(['connect failed: `:url` :message', 'url' => $url, 'message' => curl_error($curl)]);
        }

        $header_length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        $response = new Response();

        $response->url = $url;
        $response->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response->headers = explode("\r\n", substr($content, 0, $header_length - 4));
        $response->process_time = round(microtime(true) - $start_time, 3);
        $response->content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $response->body = substr($content, $header_length);

        curl_close($curl);

        return $response;
    }

    /**
     * @param array|string $url
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function get($url, $headers = [], $options = [])
    {
        return $this->request('GET', $url, null, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function post($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('POST', $url, $body, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function delete($url, $headers = [], $options = [])
    {
        return $this->request('DELETE', $url, null, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function put($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('PUT', $url, $body, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function patch($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('PATCH', $url, $body, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function head($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('HEAD', $url, $body, $headers, $options);
    }

    /**
     * @param array            $files
     * @param int|string|array $options
     *
     * @return array
     */
    public function download($files, $options = [])
    {
        if (is_int($options)) {
            $options = ['concurrent' => $options];
        } elseif (is_string($options)) {
            $options = [preg_match('#^https?://#', $options) ? CURLOPT_REFERER : CURLOPT_USERAGENT => $options];
        }

        $mh = curl_multi_init();

        $template = curl_init();

        curl_setopt($template, CURLOPT_TIMEOUT, isset($options['timeout']) ? $options['timeout'] : 10);
        curl_setopt($template, CURLOPT_CONNECTTIMEOUT, isset($options['timeout']) ? $options['timeout'] : 10);
        curl_setopt($template, CURLOPT_USERAGENT, self::USER_AGENT_IE);
        curl_setopt($template, CURLOPT_HEADER, 0);
        /** @noinspection CurlSslServerSpoofingInspection */
        curl_setopt($template, CURLOPT_SSL_VERIFYHOST, false);
        /** @noinspection CurlSslServerSpoofingInspection */
        curl_setopt($template, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($template, CURLOPT_BINARYTRANSFER, true);

        foreach ($options as $k => $v) {
            if (is_int($k)) {
                curl_setopt($template, $k, $v);
            }
        }

        foreach ($files as $url => $file) {
            $file = $this->alias->resolve($file);
            if (is_file($file)) {
                unset($files[$url]);
            } else {
                $this->filesystem->dirCreate(dirname($file));
                $files[$url] = $file;
            }
        }

        $concurrent = isset($options['concurrent']) ? $options['concurrent'] : 10;

        $handles = [];
        $failed = [];
        do {
            foreach ($files as $url => $file) {
                if (count($handles) === $concurrent) {
                    break;
                }
                $curl = curl_copy_handle($template);
                $id = (int)$curl;

                curl_setopt($curl, CURLOPT_URL, $url);
                $fp = fopen($file . '.tmp', 'wb');
                curl_setopt($curl, CURLOPT_FILE, $fp);

                curl_multi_add_handle($mh, $curl);
                $handles[$id] = ['url' => $url, 'file' => $file, 'fp' => $fp];

                unset($files[$url]);
            }

            $running = null;
            while (curl_multi_exec($mh, $running) === CURLM_CALL_MULTI_PERFORM) {
                null;
            }

            usleep(100);

            while ($info = curl_multi_info_read($mh)) {
                $curl = $info['handle'];
                $id = (int)$curl;

                $url = $handles[$id]['url'];
                $file = $handles[$id]['file'];

                fclose($handles[$id]['fp']);

                if ($info['result'] === CURLE_OK) {
                    rename($file . '.tmp', $file);
                } else {
                    $failed[$url] = curl_strerror($curl);
                    unlink($file . '.tmp');
                }

                curl_multi_remove_handle($mh, $curl);
                curl_close($curl);

                unset($handles[$id]);
            }
        } while ($handles);

        curl_multi_close($mh);
        curl_close($template);

        return $failed;
    }
}