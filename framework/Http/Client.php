<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Client\BadGatewayException;
use ManaPHP\Http\Client\BadRequestException;
use ManaPHP\Http\Client\ClientErrorException;
use ManaPHP\Http\Client\ContentTypeException;
use ManaPHP\Http\Client\EngineInterface;
use ManaPHP\Http\Client\Event\HttpClientComplete;
use ManaPHP\Http\Client\Event\HttpClientError;
use ManaPHP\Http\Client\Event\HttpClientRequested;
use ManaPHP\Http\Client\Event\HttpClientRequesting;
use ManaPHP\Http\Client\Event\HttpClientStart;
use ManaPHP\Http\Client\Event\HttpClientSuccess;
use ManaPHP\Http\Client\ForbiddenException;
use ManaPHP\Http\Client\GatewayTimeoutException;
use ManaPHP\Http\Client\InternalServerErrorException;
use ManaPHP\Http\Client\NotFoundException;
use ManaPHP\Http\Client\RedirectionException;
use ManaPHP\Http\Client\Request;
use ManaPHP\Http\Client\Response;
use ManaPHP\Http\Client\ServerErrorException;
use ManaPHP\Http\Client\ServiceUnavailableException;
use ManaPHP\Http\Client\TooManyRequestsException;
use ManaPHP\Http\Client\UnauthorizedException;
use ManaPHP\Pooling\PoolsInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function strlen;

class Client implements ClientInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected PoolsInterface $pools;

    #[Autowired] protected string $engine = 'ManaPHP\Http\Client\Engine';
    #[Autowired] protected ?string $proxy;
    #[Autowired] protected ?string $cafile;
    #[Autowired] protected int $timeout = 10;
    #[Autowired] protected bool $verify_peer = true;
    #[Autowired] protected string $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';
    #[Autowired] protected int $pool_size = 4;

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    public function request(string $method, string|array $url, null|string|array $body = null, array $headers = [],
        mixed $options = []
    ): Response {
        $headers['User-Agent'] ??= $this->user_agent;

        if (isset($headers['X-Request-Id'])) {
            SuppressWarnings::noop();
        }

        foreach ($headers as $name => $value) {
            if (is_string($name) && $value === '') {
                unset($headers[$name]);
            }
        }

        if (is_int($options) || is_float($options)) {
            $options = ['timeout' => $options];
        }

        $options['timeout'] ??= $this->timeout;
        $options['proxy'] ??= $this->proxy;
        $options['cafile'] ??= $this->cafile;
        $options['verify_peer'] ??= $this->verify_peer;

        $request = new Request($method, $url, $body, $headers, $options);

        $this->eventDispatcher->dispatch(new HttpClientStart($this, $method, $url, $request));

        try {
            $success = false;
            $response_text = null;
            $response = null;
            $engine_id = substr($request->url, 0, strpos($request->url, '/', 8) ?: 0);

            if (!$this->pools->exists($this, $engine_id)) {
                $this->pools->add($this, [$this->engine], $this->pool_size, $engine_id);
            }

            /** @var EngineInterface $engine */
            $engine = $this->pools->pop($this, $options['timeout'], $engine_id);

            try {
                $this->eventDispatcher->dispatch(new HttpClientRequesting($this, $method, $url, $request));

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
                $this->pools->push($this, $engine, $engine_id);
            }

            $response_text = $response->body;

            if ((isset($request->headers['Accept']) && str_contains($request->headers['Accept'], '/json'))
                || str_contains($response->content_type, '/json')
            ) {
                $response->body = $response->body === '' ? [] : json_parse($response->body);
            }

            $this->eventDispatcher->dispatch(new HttpClientRequested($this, $method, $url, $request, $response));
        } finally {
            if ($success) {
                $this->eventDispatcher->dispatch(new HttpClientSuccess($this, $method, $url, $request, $response));
            } else {
                $this->eventDispatcher->dispatch(new HttpClientError($this, $method, $url, $request, $response));
            }

            $this->eventDispatcher->dispatch(new HttpClientComplete($this, $method, $url, $request, $response));
        }

        $http_code = $response->http_code;
        $http_code_class = substr((string)$http_code, 0, -2) * 100;

        if ($http_code_class === 200) {
            SuppressWarnings::noop();
        } elseif ($http_code_class === 300) {
            throw new RedirectionException($response->url, $response);
        } elseif ($http_code_class === 400) {
            if ($http_code === 400) {
                throw new BadRequestException(['{1} => `{2}`', $response->url, $response_text], $response);
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

    public function rest(string $method, string|array $url, string|array $body = [], array $headers = [],
        mixed $options = []
    ): Response {
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
                $headers['Content-Type'] ??= 'application/json';
                $body = json_stringify($body);
            }
        }

        $headers['X-Requested-With'] ??= 'XMLHttpRequest';
        $headers['Accept'] ??= 'application/json';
        $headers['Accept-Encoding'] ??= 'gzip, deflate';

        if (isset($headers['Accept-Charset'], $headers['Authorization'], $headers['Cache-Control'], $headers['Host'], $headers['Cookie'])) {
            SuppressWarnings::noop();
        }

        $response = $this->request($method, $url, $body, $headers, $options);

        if (is_string($response->body) && str_contains($response->content_type, '/html')) {
            $response->body = json_parse($response->body);
        }

        if (is_string($response->body)) {
            $content_type = $response->content_type;
            throw new ContentTypeException(['content-type is not application/json: {1}', $content_type], $response);
        }

        return $response;
    }

    public function get(string|array $url, array $headers = [], mixed $options = []): Response
    {
        return $this->request('GET', $url, null, $headers, $options);
    }

    public function post(string|array $url, string|array $body = [], array $headers = [], mixed $options = []): Response
    {
        return $this->request('POST', $url, $body, $headers, $options);
    }

    public function delete(string|array $url, array $headers = [], mixed $options = []): Response
    {
        return $this->request('DELETE', $url, null, $headers, $options);
    }

    public function put(string|array $url, string|array $body = [], array $headers = [], mixed $options = []): Response
    {
        return $this->request('PUT', $url, $body, $headers, $options);
    }

    public function patch(string|array $url, string|array $body = [], array $headers = [], mixed $options = []
    ): Response {
        return $this->request('PATCH', $url, $body, $headers, $options);
    }

    public function head(string|array $url, string|array $body = [], array $headers = [], mixed $options = []): Response
    {
        return $this->request('HEAD', $url, $body, $headers, $options);
    }
}