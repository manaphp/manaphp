<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\NonCloneableException;
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
 * @property-read \ManaPHP\AliasInterface        $alias
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component implements ClientInterface
{
    const USER_AGENT_IE = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';

    /**
     * @var string|\ManaPHP\Http\Client\EngineInterface
     */
    protected $engine;

    /**
     * @var string
     */
    protected $proxy = '';

    /**
     * @var string
     */
    protected $cafile = '';

    /**
     * @var int
     */
    protected $timeout = 10;

    /**
     * @var bool
     */
    protected $verify_peer = true;

    /**
     * @var string
     */
    protected $user_agent;

    /**
     * @var int
     */
    protected $pool_size = 4;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if ($engine = $options['engine'] ?? null) {
            $this->engine = str_contains($engine, '\\') ? $engine : "ManaPHP\Http\Client\Engine\\" . ucfirst($engine);
        } else {
            $this->engine = 'ManaPHP\Http\Client\Engine\Fopen';
        }

        if (isset($options['proxy'])) {
            $this->proxy = $options['proxy'];
        }

        if (isset($options['cafile'])) {
            $this->cafile = $options['cafile'];
        }

        if (isset($options['timeout'])) {
            $this->timeout = (int)$options['timeout'];
        }

        if (isset($options['verify_peer'])) {
            $this->verify_peer = (bool)$options['verify_peer'];
        }

        $this->user_agent = $options['user_agent'] ?? self::USER_AGENT_IE;

        if (isset($options['pool_size'])) {
            $this->pool_size = (int)$options['pool_size'];
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
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function request($method, $url, $body = null, $headers = [], $options = [])
    {
        if (!isset($headers['User-Agent'])) {
            $headers['User-Agent'] = $this->user_agent;
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
            $options['timeout'] = $this->timeout;
        }

        if (!isset($options['proxy'])) {
            $options['proxy'] = $this->proxy;
        }

        if (!isset($options['cafile'])) {
            $options['cafile'] = $this->cafile;
        }

        if (!isset($options['verify_peer'])) {
            $options['verify_peer'] = $this->verify_peer;
        }

        $request = new Request($method, $url, $body, $headers, $options);

        $this->fireEvent('httpClient:start', compact('method', 'url', 'request'));

        try {
            $success = false;
            $response_text = null;
            $response = null;
            $engine_id = substr($request->url, 0, strpos($request->url, '/', 8));

            if (!$this->poolManager->exists($this, $engine_id)) {
                $this->poolManager->add($this, $this->engine, $this->pool_size, $engine_id);
            }

            /** @var \ManaPHP\Http\Client\EngineInterface $engine */
            $engine = $this->poolManager->pop($this, $options['timeout'], $engine_id);

            try {
                $this->fireEvent('httpClient:requesting', compact('method', 'url', 'request'));

                if ($request->hasFile()) {
                    $boundary = '------------------------' . bin2hex(random_bytes(8));
                    $request->headers['Content-Type'] = "multipart/form-data; boundary=$boundary";
                    $body = $request->buildMultipart($boundary);
                } else {
                    $body = $request->body;
                }

                if (is_array($body)) {
                    if (isset($request->headers['Content-Type'])
                        && str_contains(
                            $request->headers['Content-Type'], 'json'
                        )
                    ) {
                        $body = json_stringify($body);
                    } else {
                        $body = http_build_query($body);
                    }
                }

                if (is_string($body)) {
                    $request->headers['Content-Length'] = strlen($body);
                }

                $response = $engine->request($request, $body);

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
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function rest($method, $url, $body = [], $headers = [], $options = [])
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

        $response = $this->self->request($method, $url, $body, $headers, $options);

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
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function get($url, $headers = [], $options = [])
    {
        return $this->self->request('GET', $url, null, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function post($url, $body = [], $headers = [], $options = [])
    {
        return $this->self->request('POST', $url, $body, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function delete($url, $headers = [], $options = [])
    {
        return $this->self->request('DELETE', $url, null, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function put($url, $body = [], $headers = [], $options = [])
    {
        return $this->self->request('PUT', $url, $body, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function patch($url, $body = [], $headers = [], $options = [])
    {
        return $this->self->request('PATCH', $url, $body, $headers, $options);
    }

    /**
     * @param array|string    $url
     * @param string|array    $body
     * @param array           $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function head($url, $body = [], $headers = [], $options = [])
    {
        return $this->self->request('HEAD', $url, $body, $headers, $options);
    }
}