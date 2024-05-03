<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Router\Event\RouterRouted;
use ManaPHP\Http\Router\Event\RouterRouting;
use ManaPHP\Http\Router\Matcher;
use ManaPHP\Http\Router\MatcherInterface;
use ManaPHP\Http\Router\PathsNormalizerInterface;
use ManaPHP\Http\Router\Route;
use ManaPHP\Http\Router\RouteInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function count;
use function http_build_query;
use function is_string;
use function parse_str;
use function strlen;
use function strpbrk;
use function strpos;
use function substr;

class Router implements RouterInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected PathsNormalizerInterface $pathsNormalizer;

    #[Autowired] protected bool $case_sensitive = true;
    #[Autowired] protected string $prefix = '';

    /**
     * @var RouteInterface[][]
     */
    protected array $literals = [];

    /**
     * @var RouteInterface[]
     */
    protected array $regexes = [];

    public function isCaseSensitive(): bool
    {
        return $this->case_sensitive;
    }

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

    public function addWithMethod(string $method, string $pattern, string|array $handler): RouteInterface
    {
        $handler = $this->pathsNormalizer->normalize($handler);
        $route = new Route($method, $pattern, $handler, $this->case_sensitive);
        if (strpbrk($pattern, ':{') === false) {
            $this->literals[$method][$pattern] = $route;
        } else {
            $this->regexes[] = $route;
        }

        return $route;
    }

    public function add(string $pattern, string|array $handler): RouteInterface
    {
        return $this->addWithMethod('*', $pattern, $handler);
    }

    public function addGet(string $pattern, string|array $handler): RouteInterface
    {
        return $this->addWithMethod('GET', $pattern, $handler);
    }

    public function addPost(string $pattern, string|array $handler): RouteInterface
    {
        return $this->addWithMethod('POST', $pattern, $handler);
    }

    public function addPut(string $pattern, string|array $handler): RouteInterface
    {
        return $this->addWithMethod('PUT', $pattern, $handler);
    }

    public function addPatch(string $pattern, string|array $handler): RouteInterface
    {
        return $this->addWithMethod('PATCH', $pattern, $handler);
    }

    public function addDelete(string $pattern, string|array $handler): RouteInterface
    {
        return $this->addWithMethod('DELETE', $pattern, $handler);
    }

    public function addHead(string $pattern, string|array $handler): RouteInterface
    {
        return $this->addWithMethod('HEAD', $pattern, $handler);
    }

    public function addRest(string $pattern, string $controller): RouteInterface
    {
        $pattern .= '(/{id:[-\w]+})?';
        $paths = $this->pathsNormalizer->normalize($controller);
        $route = new Route('REST', $pattern, $paths, $this->case_sensitive);
        $this->regexes[] = $route;

        return $route;
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
            $parts = null;
        } elseif (($route = $this->literals[$method][$handledUri] ?? $this->literals['*'][$handledUri] ?? null)
            !== null
        ) {
            $parts = $route->match($handledUri, $method);
        } else {
            $parts = null;
            for ($i = count($this->regexes) - 1; $i >= 0; $i--) {
                $route = $this->regexes[$i];
                if (($parts = $route->match($handledUri, $method)) !== null) {
                    break;
                }
            }
        }

        if ($parts === null) {
            $this->eventDispatcher->dispatch(new RouterRouted($this, null));

            return null;
        }

        if (isset($parts['area'])) {
            $area = Str::pascalize($parts['area']);
        } else {
            $area = null;
        }

        $controller = Str::pascalize($parts['controller']);
        $action = Str::camelize($parts['action']);

        if (isset($parts['area'])) {
            $handler = "App\\Areas\\$area\Controllers\\{$controller}Controller::{$action}Action";
        } else {
            $handler = "App\\Controllers\\{$controller}Controller::{$action}Action";
        }

        $matcher = new Matcher($handler, $parts['params'] ?? []);
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
