<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Router\Event\RouterRouted;
use ManaPHP\Http\Router\Event\RouterRouting;
use ManaPHP\Http\Router\Matcher;
use ManaPHP\Http\Router\MatcherInterface;
use ManaPHP\Http\Router\PatternCompilerInterface;
use ManaPHP\Http\Router\RegexRoute;
use ManaPHP\Http\Router\RestRoute;
use ManaPHP\Http\Router\RouteInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function count;
use function http_build_query;
use function implode;
use function is_array;
use function is_string;
use function parse_str;
use function str_contains;
use function strlen;
use function strpos;
use function substr;

class Router implements RouterInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected PatternCompilerInterface $patternCompiler;

    #[Autowired] protected string $prefix = '';

    /**
     * @var string[][]
     */
    protected array $literals = [];

    /**
     * @var RouteInterface[]
     */
    protected array $dynamics = [];

    public function setPrefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getPrefix(): string
    {
        $prefix = $this->prefix;
        if (str_starts_with($prefix, '?')) {
            $uri = $this->request->path();
            $pattern = '#^/[\w-]+' . substr($prefix, 1) . '#';
            if (preg_match($pattern, $uri, $match)) {
                return $match[0];
            } else {
                return substr($prefix, 1);
            }
        } else {
            return $prefix;
        }
    }

    public function add(string $method, string $pattern, string|array $handler): void
    {
        if (is_array($handler)) {
            $handler = implode('::', $handler);
        }

        $compiled = $this->patternCompiler->compile($pattern);
        $route = new RegexRoute($method, $pattern, $compiled, $handler);
        if (!str_contains($pattern, '{')) {
            $this->literals[$method][$pattern] = $handler;
        } else {
            $this->dynamics[] = $route;
        }
    }

    public function addAny(string $pattern, string|array $handler): void
    {
        $this->add('*', $pattern, $handler);
    }

    public function addGet(string $pattern, string|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function addPost(string $pattern, string|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function addPut(string $pattern, string|array $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function addPatch(string $pattern, string|array $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function addDelete(string $pattern, string|array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function addHead(string $pattern, string|array $handler): void
    {
        $this->add('HEAD', $pattern, $handler);
    }

    public function addOptions(string $pattern, string|array $handler): void
    {
        $this->add('OPTIONS', $pattern, $handler);
    }

    public function addRest(string $pattern, string $controller): void
    {
        $pattern .= '(/{id:[-\w]+})?';
        $compiled = $this->patternCompiler->compile($pattern);
        $route = new RestRoute($pattern, $compiled, $controller . '::{action}Action');
        $this->dynamics[] = $route;
    }

    public function getRewriteUri(): string
    {
        if (($url = $this->request->input('_url', '')) === '') {
            $request_uri = $this->request->path();
            $pos = strpos($request_uri, '?');
            $url = $pos === false ? $request_uri : substr($request_uri, 0, $pos);
        }

        $url = rtrim($url, '/') ?: '/';

        if ($url[0] !== '/') {
            $url = parse_url($url, PHP_URL_PATH);
        }

        return $url;
    }

    public function match(?string $uri = null, ?string $method = null): ?MatcherInterface
    {
        $this->eventDispatcher->dispatch(new RouterRouting($this));

        $uri = $uri ?: $this->getRewriteUri();

        if ($method === null) {
            $method = $this->request->method();
        }

        if (($prefix = $this->getPrefix()) !== '') {
            if (str_starts_with($uri, $prefix)) {
                $handledUri = substr($uri, strlen($prefix)) ?: '/';
            } else {
                $handledUri = false;
            }
        } else {
            $handledUri = $uri;
        }

        if ($handledUri === false) {
            $matcher = null;
        } elseif (($handler = $this->literals[$method][$handledUri] ?? $this->literals['*'][$handledUri] ?? null)
            !== null
        ) {
            $matcher = new Matcher($handler, []);
        } else {
            $matcher = null;
            for ($i = count($this->dynamics) - 1; $i >= 0; $i--) {
                $route = $this->dynamics[$i];
                if (($matcher = $route->match($handledUri, $method)) !== null) {
                    break;
                }
            }
        }

        $this->eventDispatcher->dispatch(new RouterRouted($this, $matcher));

        return $matcher;
    }

    public function createUrl(string|array $args, bool|string $scheme = false): string
    {
        if (is_string($args)) {
            if (($pos = strpos($args, '?')) !== false) {
                $path = substr($args, 0, $pos);
                parse_str(substr($args, $pos + 1), $params);
            } else {
                $path = $args;
                $params = [];
            }
        } else {
            $path = $args[0];
            unset($args[0]);
            $params = $args;
        }

        $url = $this->getPrefix() . $path;

        if ($params !== []) {
            $fragment = null;
            if (isset($params['#'])) {
                $fragment = $params['#'];
                unset($params['#']);
            }

            if ($params !== []) {
                $url .= '?' . http_build_query($params);
            }
            if ($fragment !== null) {
                $url .= '#' . $fragment;
            }
        }

        if ($scheme) {
            if ($scheme === true) {
                $scheme = $this->request->scheme();
            }
            return ($scheme === '//' ? '//' : "$scheme://") . $this->request->header('host') . $url;
        } else {
            return $url;
        }
    }
}
