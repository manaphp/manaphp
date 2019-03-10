<?php
namespace ManaPHP\Curl;

use ManaPHP\Component;
use ManaPHP\Curl\Easy\BadRequestException;
use ManaPHP\Curl\Easy\ContentTypeException;
use ManaPHP\Curl\Easy\ForbiddenException;
use ManaPHP\Curl\Easy\JsonDecodeException;
use ManaPHP\Curl\Easy\Response;
use ManaPHP\Curl\Easy\ServiceUnavailableException;
use ManaPHP\Curl\Easy\TooManyRequestsException;
use ManaPHP\Curl\Easy\UnauthorizedException;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;

class EasyContext
{
    /**
     * @var \ManaPHP\Curl\Easy\Response
     */
    public $lastResponse;
}

/**
 * Class ManaPHP\Curl\Easy
 *
 * @package Curl
 */
class Easy extends Component implements EasyInterface
{
    const HEADER_USER_AGENT = CURLOPT_USERAGENT;
    const HEADER_REFERER = CURLOPT_REFERER;
    const HEADER_COOKIE = CURLOPT_COOKIE;

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
    protected $_caFile;

    /**
     * @var int
     */
    protected $_timeout = 30;

    /**
     * @var bool
     */
    protected $_sslVerify = true;

    /**
     * Client constructor.
     *
     * @param array $options
     *
     * - `User-Agent`: User Agent to send to the server
     *   (string, default: php-requests/$version)
     */
    public function __construct($options = [])
    {
        $this->_context = new EasyContext();

        if (!function_exists('curl_init')) {
            throw new ExtensionNotInstalledException('curl');
        }

        if (isset($options['peek'])) {
            $this->_peek = (bool)$options['peek'];
        }

        if (isset($options['proxy'])) {
            $this->_proxy = $options['proxy'];
        }

        if (isset($options['caFile'])) {
            $this->_caFile = $options['caFile'];
        }

        if (isset($options['timeout'])) {
            $this->_timeout = (int)$options['timeout'];
        }

        if (isset($options['sslVerify'])) {
            $this->_sslVerify = (bool)$options['sslVerify'];
        }

        if (isset($options['options'])) {
            $this->_options = $options['options'];
        }
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
     * @param string                 $type
     * @param string|array           $url
     * @param string|array           $body
     * @param array|string|int|float $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     * @throws \ManaPHP\Curl\Easy\ForbiddenException
     * @throws \ManaPHP\Curl\Easy\TooManyRequestsException
     * @throws \ManaPHP\Curl\Easy\ServiceUnavailableException
     * @throws \ManaPHP\Curl\Easy\BadRequestException
     * @throws \ManaPHP\Curl\ConnectionException
     * @throws \ManaPHP\Curl\Easy\UnauthorizedException
     */
    public function request($type, $url, $body = null, $options = [])
    {
        $context = $this->_context;

        $context->lastResponse = null;

        if (is_array($url)) {
            if (count($url) > 1) {
                $uri = $url[0];
                unset($url[0]);
                $url = $uri . (strpos($uri, '?') !== false ? '&' : '?') . http_build_query($url);
            } else {
                $url = $url[0];
            }
        }

        $curl = curl_init();

        $this->fireEvent('curl:beforeRequest', compact('type', 'url', 'body', 'options', 'curl'));

        if (is_int($options) || is_float($options)) {
            $options = ['timeout' => $options];
        } elseif (is_string($options)) {
            $options = [strpos($options, '://') ? CURLOPT_REFERER : CURLOPT_USERAGENT => $options];
        }

        if ($this->_options) {
            $options = array_merge($options, $this->_options);
        }

        if (isset($options['-'])) {
            $options[CURLOPT_REFERER] = $options['-'];
            unset($options['-']);
        } elseif (isset($options['Referer'])) {
            $options[CURLOPT_REFERER] = $options['Referer'];
            unset($options['Referer']);
        }

        if (isset($options[CURLOPT_REFERER])) {
            if (is_array($options[CURLOPT_REFERER])) {
                $referer = $options[CURLOPT_REFERER];
                if (isset($referer[0])) {
                    $str = $referer[0];
                    unset($referer[0]);
                } else {
                    $str = 'http://TRACK/';
                }
                $options[CURLOPT_REFERER] = $str . (strpos($str, '?') ? '&' : '?') . http_build_query($referer);
            } elseif (!strpos($options[CURLOPT_REFERER], '://')) {
                $options[CURLOPT_REFERER] = 'http://TRACE/' . $options[CURLOPT_REFERER];
            }
        }

        if (isset($options['Cookie'])) {
            $options[CURLOPT_COOKIE] = $options['Cookie'];
            unset($options['Cookie']);
        }

        if (isset($options['User-Agent'])) {
            $options[CURLOPT_USERAGENT] = $options['User-Agent'];
            unset($options['User-Agent']);
        } else {
            $options[CURLOPT_USERAGENT] = self::USER_AGENT_IE;
        }

        if (preg_match('/^http(s)?:\/\//i', $url) !== 1) {
            throw new NotSupportedException(['only HTTP requests can be handled: `:url`', 'url' => $url]);
        }

        $this->logger->debug([['METHOD' => $type, 'URL' => $url, 'OPTIONS' => $options, 'BODY' => $body]], 'httpClient.request');

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 8);

        if (is_array($body)) {
            if (isset($options['Content-Type']) && strpos($options['Content-Type'], 'json') !== false) {
                $body = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $hasFiles = false;
                /** @noinspection ForeachSourceInspection */
                foreach ($body as $k => $v) {
                    if (is_string($v) && strlen($v) > 1 && $v[0] === '@' && $this->filesystem->fileExists($v)) {
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
            case 'OPTIONS':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
                break;
            default:
                throw new NotSupportedException(['`:method` method is not support', 'method' => $type]);
                break;
        }

        if (isset($options['timeout'])) {
            $timeout = $options['timeout'];
            unset($options['timeout']);
        } else {
            $timeout = $this->_timeout;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
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

        if ($this->_caFile) {
            curl_setopt($curl, CURLOPT_CAINFO, $this->alias->resolve($this->_caFile));
        }

        if (!$this->_sslVerify || $this->_peek) {
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        $headers = [];
        foreach ($options as $k => $v) {
            if (is_int($k)) {
                curl_setopt($curl, $k, $v);
            } else {
                $headers[] = $k . ': ' . $v;
            }
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
        if (($errno = curl_errno($curl)) === CURLE_SSL_CACERT && !$this->_caFile && DIRECTORY_SEPARATOR === '\\') {
            $this->logger->warn('ca.pem file is not exists,so https verify is disabled, you should download from https://curl.haxx.se/ca/cacert.pem','httpClient.noCaCert');
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $content = curl_exec($curl);
            $errno = curl_error($curl);
        }

        if ($errno) {
            throw new ConnectionException(['connect failed: `:url` :message', 'url' => $url, 'message' => curl_error($curl)]);
        }

        $header_length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        $response = new Response();

        $response->url = $url;
        $response->remote_ip = curl_getinfo($curl, CURLINFO_PRIMARY_IP);
        $response->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response->headers = explode("\r\n", substr($content, 0, $header_length - 4));
        $response->process_time = round(microtime(true) - $start_time, 3);
        $response->content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $response->body = substr($content, $header_length);
        $response->stats = ['total_time' => curl_getinfo($curl, CURLINFO_TOTAL_TIME),
            'namelookup_time' => curl_getinfo($curl, CURLINFO_NAMELOOKUP_TIME),
            'connect_time' => curl_getinfo($curl, CURLINFO_CONNECT_TIME),
            'pretransfer_time' => curl_getinfo($curl, CURLINFO_PRETRANSFER_TIME),
            'starttransfer_time' => curl_getinfo($curl, CURLINFO_STARTTRANSFER_TIME)];

        curl_close($curl);

        $this->fireEvent('curl:afterRequest', compact('type', 'url', 'body', 'options', 'response'));

        $this->logger->debug([[
            'METHOD' => $type,
            'URL' => $response->url,
            'HTTP_CODE' => $response->http_code,
            'REFERER' => isset($options[CURLOPT_REFERER]) ? $options[CURLOPT_REFERER] : '',
            'REQUEST_BODY' => $body,
            'HEADERS' => $response->getHeaders(),
            'STATS' => $response->stats,
            'BODY' => strpos($response->content_type, 'json') !== false ? $response->getJsonBody() : $response->getUtf8Body()]],
            'httpClient.response');

        $context->lastResponse = $response;

        if ($response->http_code === 429) {
            throw new TooManyRequestsException($response->url, $response);
        } elseif ($response->http_code === 403) {
            throw new ForbiddenException($response->url, $response);
        } elseif ($response->http_code === 401) {
            throw new UnauthorizedException($response->url, $response);
        }

        if ($response->http_code >= 500) {
            throw new ServiceUnavailableException(['service is unavailable: :http_code => `:url`', 'http_code' => $response->http_code, 'url' => $response->url], $response);
        }

        if ($response->http_code >= 400) {
            throw new BadRequestException(['bad request: :http_code => `:url`', 'http_code' => $response->http_code, 'url' => $response->url], $response);
        }

        return $context->lastResponse;
    }

    /**
     * @param string           $type
     * @param string|array     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return array
     * @throws \ManaPHP\Curl\Easy\ForbiddenException
     * @throws \ManaPHP\Curl\Easy\TooManyRequestsException
     * @throws \ManaPHP\Curl\Easy\ServiceUnavailableException
     * @throws \ManaPHP\Curl\Easy\BadRequestException
     * @throws \ManaPHP\Curl\Easy\ContentTypeException
     * @throws \ManaPHP\Curl\Easy\JsonDecodeException
     * @throws \ManaPHP\Curl\ConnectionException
     * @throws \ManaPHP\Curl\Easy\UnauthorizedException
     */
    public function rest($type, $url, $body = null, $options = [])
    {
        if (isset($options['Content-Type']) && strpos($options['Content-Type'], 'json') === false) {
            throw new InvalidValueException(['Content-Type of rest is not application/json: :content-type', 'content-type' => $options['Content-Type']]);
        } else {
            $options['Content-Type'] = 'application/json';
        }

        if (!isset($options['X-Requested-With'])) {
            $options['X-Requested-With'] = 'XMLHttpRequest';
        }

        if (!isset($options['Accept'])) {
            $options['Accept'] = 'application/json';
        }

        if (is_array($body)) {
            $body = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $response = $this->request($type, $url, $body, $options);

        if (strpos($response->content_type, 'json') === false) {
            throw new ContentTypeException(['content-type of response is not application/json: :content-type => `:url`',
                'content-type' => $response->content_type, 'url' => $response->url],
                $response);
        }

        $json = json_decode($response->body, true);
        if (!is_array($json)) {
            throw new JsonDecodeException(['json decode failed: :error => `:url`',
                'url' => $response->url, 'error' => json_last_error_msg()],
                $response);
        }
        return ['response' => $response, 'http_code' => $response->http_code, 'body' => $json];
    }

    /**
     * @param array|string     $url
     * @param array|string|int $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function get($url, $options = [])
    {
        return $this->request('GET', $url, null, $options);
    }

    /**
     * @param array|string     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function post($url, $body = [], $options = [])
    {
        return $this->request('POST', $url, $body, $options);
    }

    /**
     * @param array|string     $url
     * @param array|string|int $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function delete($url, $options = [])
    {
        return $this->request('DELETE', $url, null, $options);
    }

    /**
     * @param array|string     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function put($url, $body = [], $options = [])
    {
        return $this->request('PUT', $url, $body, $options);
    }

    /**
     * @param array|string     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function patch($url, $body = [], $options = [])
    {
        return $this->request('PATCH', $url, $body, $options);
    }

    /**
     * @param array|string     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function head($url, $body = [], $options = [])
    {
        return $this->request('HEAD', $url, $body, $options);
    }

    /**
     * @param string|array           $files
     * @param string|int|array|float $options
     *
     * @return string|array
     */
    public function download($files, $options = [])
    {
        if (is_string($files)) {
            if (is_string($options) && strpos($options, '://') === false) {
                $return_file = $options;
            } else {
                $path = parse_url($files, PHP_URL_PATH);
                if ($pos = strrpos($path, '.')) {
                    $ext = strtolower(substr($path, $pos));
                    if ($ext === '.php' || preg_match('#^\.\w+$#', $ext) === 0) {
                        $ext = '.tmp';
                    }
                } else {
                    $ext = '.tmp';
                }
                $return_file = $this->alias->resolve('@tmp/download/' . md5($files . gethostname()) . $ext);
            }
            $files = [$files => $return_file];
        } else {
            if (is_int($options)) {
                $options = ['concurrent' => $options];
            } elseif (is_float($options)) {
                $options = ['timeout' => $options];
            } elseif (is_string($options)) {
                $options = [preg_match('#^https?://#', $options) ? CURLOPT_REFERER : CURLOPT_USERAGENT => $options];
            }
            $return_file = null;
        }

        $mh = curl_multi_init();

        $template = curl_init();

        if (isset($options['timeout'])) {
            $timeout = $options['timeout'];
            unset($options['timeout']);
        } else {
            $timeout = 10;
        }

        if (isset($options['concurrent'])) {
            $concurrent = $options['concurrent'];
            unset($options['concurrent']);
        } else {
            $concurrent = 10;
        }

        curl_setopt($template, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($template, CURLOPT_CONNECTTIMEOUT, $timeout);
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

        if ($return_file) {
            return $failed ? false : $return_file;
        } else {
            return $failed;
        }
    }

    /**
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function getLastResponse()
    {
        return $this->_context->lastResponse;
    }
}