<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\AliasInterface;
use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Event\EventTrait;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Http\Client\BadGatewayException;
use ManaPHP\Http\Client\BadRequestException;
use ManaPHP\Http\Client\ClientErrorException;
use ManaPHP\Http\Client\ContentTypeException;
use ManaPHP\Http\Client\EngineInterface;
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
use ManaPHP\Pool\ManagerInterface;

class Client extends Component implements ClientInterface
{
    use EventTrait;

    #[Inject]
    protected AliasInterface $alias;
    #[Inject]
    protected ManagerInterface $poolManager;
    #[Inject]
    protected FactoryInterface $factory;

    public const USER_AGENT_IE = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';
    protected string|EngineInterface $engine;
    protected ?string $proxy;
    protected ?string $cafile;
    protected int $timeout;
    protected bool $verify_peer;
    protected string $user_agent;
    protected int $pool_size;

    public function __construct(string $engine = 'ManaPHP\Http\Client\Engine\Fopen',
        ?string $proxy = null, ?string $cafile = null, int $timeout = 10, bool $verify_peer = true,
        string $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        int $pool_size = 4
    ) {
        $this->engine = str_contains($engine, '\\') ? $engine : "ManaPHP\Http\Client\Engine\\" . ucfirst($engine);
        $this->proxy = $proxy;
        $this->cafile = $cafile;
        $this->timeout = $timeout;
        $this->verify_peer = $verify_peer;
        $this->user_agent = $user_agent;
        $this->pool_size = $pool_size;
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    public function request(string $method, string|array $url, null|string|array $body = null, array $headers = [],
        mixed $options = []
    ): Response {
        $headers['User-Agent'] ??= $this->user_agent;

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

        $options['timeout'] ??= $this->timeout;
        $options['proxy'] ??= $this->proxy;
        $options['cafile'] ??= $this->cafile;
        $options['verify_peer'] ??= $this->verify_peer;

        $request = new Request($method, $url, $body, $headers, $options);

        $this->fireEvent('httpClient:start', compact('method', 'url', 'request'));

        try {
            $success = false;
            $response_text = null;
            $response = null;
            $engine_id = substr($request->url, 0, strpos($request->url, '/', 8) ?: 0);

            if (!$this->poolManager->exists($this, $engine_id)) {
                $sample = is_string($this->engine) ? $this->factory->make($this->engine) : $this->engine;
                $this->poolManager->add($this, $sample, $this->pool_size, $engine_id);
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
        $http_code_class = substr((string)$http_code, 0, -2) * 100;

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