<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\Client\BadGatewayException;
use ManaPHP\Http\Client\BadRequestException;
use ManaPHP\Http\Client\ClientErrorException;
use ManaPHP\Http\Client\ContentTypeException;
use ManaPHP\Http\Client\ForbiddenException;
use ManaPHP\Http\Client\GatewayTimeoutException;
use ManaPHP\Http\Client\InternalServerErrorException;
use ManaPHP\Http\Client\NotFoundException;
use ManaPHP\Http\Client\RedirectionException;
use ManaPHP\Http\Client\Request;
use ManaPHP\Http\Client\ServerErrorException;
use ManaPHP\Http\Client\ServiceUnavailableException;
use ManaPHP\Http\Client\TooManyRequestsException;
use ManaPHP\Http\Client\UnauthorizedException;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component implements ClientInterface
{
    const USER_AGENT_IE = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';

    /**
     * @var string|\ManaPHP\Http\Client\EngineInterface
     */
    protected $_engine;

    /**
     * @var string
     */
    protected $_proxy = '';

    /**
     * @var string
     */
    protected $_cafile = '';

    /**
     * @var int
     */
    protected $_timeout = 10;

    /**
     * @var bool
     */
    protected $_verify_peer = true;

    /**
     * @var string
     */
    protected $_user_agent;

    /**
     * @var bool
     */
    protected $_keepalive = false;

    /**
     * @var int
     */
    protected $_pool_size = 4;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if ($engine = $options['engine'] ?? null) {
            $this->_engine = str_contains($engine, '\\') ? $engine : "ManaPHP\Http\Client\Engine\\" . ucfirst($engine);
        } else {
            $this->_engine = 'ManaPHP\Http\Client\Engine\Stream';
        }

        if (isset($options['proxy'])) {
            $this->_proxy = $options['proxy'];
        }

        if (isset($options['cafile'])) {
            $this->_cafile = $options['cafile'];
        }

        if (isset($options['timeout'])) {
            $this->_timeout = (int)$options['timeout'];
        }

        if (isset($options['verify_peer'])) {
            $this->_verify_peer = (bool)$options['verify_peer'];
        }

        $this->_user_agent = $options['user_agent'] ?? self::USER_AGENT_IE;

        if (isset($options['keepalive'])) {
            $this->_keepalive = (bool)$options['keepalive'];
        }

        if (isset($options['pool_size'])) {
            $this->_pool_size = (int)$options['pool_size'];
        }
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    /**
     * @param string          $method
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function request($method, $url, $body = null, $headers = [], $options = [])
    {
        if (is_string($headers)) {
            $headers = [(str_contains($headers, '://') ? 'Referer' : 'User-Agent') => $headers];
        }

        if (!isset($headers['User-Agent'])) {
            $headers['User-Agent'] = $this->_user_agent;
        }

        if (isset($headers['X-Request-Id'])) {
            null;//code completion
        }

        foreach ($headers as $name => $value) {
            if (is_string($name) && $value === '') {
                unset($headers[$name]);
            }
        }

        if (is_int($options) || is_float($options)) {
            $options = ['timeout' => $options];
        }

        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->_timeout;
        }

        if (!isset($options['proxy'])) {
            $options['proxy'] = $this->_proxy;
        }

        if (!isset($options['cafile'])) {
            $options['cafile'] = $this->_cafile;
        }

        if (!isset($options['verify_peer'])) {
            $options['verify_peer'] = $this->_verify_peer;
        }

        $request = new Request($method, $url, $body, $headers, $options);

        $this->fireEvent('httpClient:start', compact('method', 'url', 'request'));

        try {
            $success = false;
            $response_text = null;
            $response = null;
            $engine_id = substr($request->url, 0, strpos($request->url, '/', 8));

            if (!$this->poolManager->exists($this, $engine_id)) {
                $this->poolManager->add($this, $this->_engine, $this->_pool_size, $engine_id);
            }

            /** @var \ManaPHP\Http\Client\EngineInterface $engine */
            $engine = $this->poolManager->pop($this, $options['timeout'], $engine_id);

            try {
                $this->fireEvent('httpClient:requesting', compact('method', 'url', 'request'));

                $response = $engine->request($request, $this->_keepalive);

                $success = true;
            } finally {
                $this->poolManager->push($this, $engine, $engine_id);
            }

            $response_text = $response->body;

            if ((isset($request->headers['Accept']) && str_contains($request->headers['Accept'], '/json'))
                || str_contains($response->content_type, '/json')
            ) {
                $response->body = $response->body === '' ? [] : json_parse($response->body);
            }

            $this->fireEvent('httpClient:requested', compact('method', 'url', 'request', 'response'));
        } finally {
            if ($success) {
                $this->fireEvent('httpClient:success', compact('method', 'url', 'request', 'response'));
            } else {
                $this->fireEvent('httpClient:error', compact('method', 'url', 'request'));
            }

            $this->fireEvent('httpClient:complete', compact('method', 'url', 'request', 'response'));
        }

        $http_code = $response->http_code;
        $http_code_class = substr($http_code, 0, -2) * 100;

        if ($http_code_class === 200) {
            null;
        } elseif ($http_code_class === 300) {
            throw new RedirectionException($response->url, $response);
        } elseif ($http_code_class === 400) {
            if ($http_code === 400) {
                throw new BadRequestException(['%s => `%s`', $response->url, $response_text], $response);
            } elseif ($http_code === 401) {
                throw new UnauthorizedException($response->url, $response);
            } elseif ($http_code === 403) {
                throw new ForbiddenException($response->url, $response);
            } elseif ($http_code === 404) {
                throw new NotFoundException($response->url, $response);
            } elseif ($http_code === 429) {
                throw new TooManyRequestsException($response->url, $response);
            } else {
                throw new ClientErrorException($response->url, $response);
            }
        } elseif ($http_code_class === 500) {
            if ($http_code === 500) {
                throw new InternalServerErrorException($response->url, $response);
            } elseif ($http_code === 502) {
                throw new BadGatewayException($response->url, $response);
            } elseif ($http_code === 503) {
                throw new ServiceUnavailableException($response->url, $response);
            } elseif ($http_code === 504) {
                throw new GatewayTimeoutException($response->url, $response);
            } else {
                throw new ServerErrorException($response->url, $response);
            }
        }

        return $response;
    }

    /**
     * @param string          $method
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function rest($method, $url, $body = null, $headers = [], $options = [])
    {
        if (is_string($body)) {
            if (!isset($headers['Content-Type'])) {
                if (preg_match('#^\[|{#', $body)) {
                    $headers['Content-Type'] = 'application/json';
                } else {
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                }
            }
        } else {
            if (($headers['Content-Type'] ?? null) === 'application/x-www-form-urlencoded') {
                $body = http_build_query($body);
            } else {
                if (!isset($headers['Content-Type'])) {
                    $headers['Content-Type'] = 'application/json';
                }
                $body = json_stringify($body);
            }
        }

        if (!isset($headers['X-Requested-With'])) {
            $headers['X-Requested-With'] = 'XMLHttpRequest';
        }

        if (!isset($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }

        if (!isset($headers['Accept-Encoding'])) {
            $headers['Accept-Encoding'] = 'gzip, deflate';
        }

        if (isset($headers['Accept-Charset'], $headers['Authorization'], $headers['Cache-Control'], $headers['Host'], $headers['Cookie'])) {
            null;
        }

        $response = $this->request($method, $url, $body, $headers, $options);

        if (is_string($response->body) && str_contains($response->content_type, '/html')) {
            $response->body = json_parse($response->body);
        }

        if (is_string($response->body)) {
            $content_type = $response->content_type;
            throw new ContentTypeException(['content-type is not application/json: %s', $content_type], $response);
        }

        return $response;
    }

    /**
     * @param array|string    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function get($url, $headers = [], $options = [])
    {
        return $this->request('GET', $url, null, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function post($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('POST', $url, $body, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function delete($url, $headers = [], $options = [])
    {
        return $this->request('DELETE', $url, null, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function put($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('PUT', $url, $body, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function patch($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('PATCH', $url, $body, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function head($url, $body = [], $headers = [], $options = [])
    {
        return $this->request('HEAD', $url, $body, $headers, $options);
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
            if (is_string($options) && !str_contains($options, '://')) {
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
                LocalFS::dirCreate(dirname($file));
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
}