<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\AliasInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Router\Event\RouterRouted;
use ManaPHP\Http\Router\Event\RouterRouting;
use ManaPHP\Http\Router\PathsNormalizerInterface;
use ManaPHP\Http\Router\Route;
use ManaPHP\Http\Router\RouteInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function count;
use function in_array;
use function is_string;
use function strlen;
use function strpbrk;

class Router implements RouterInterface
{
    use ContextTrait;

    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected PathsNormalizerInterface $pathsNormalizer;

    #[Autowired] protected bool $case_sensitive = true;
    #[Autowired] protected string $prefix = '';
    #[Autowired] protected array $areas = [];

    /**
     * @var RouteInterface[]
     */
    protected array $defaults = [];

    /**
     * @var RouteInterface[][]
     */
    protected array $literals = [];

    /**
     * @var RouteInterface[]
     */
    protected array $regexes = [];

    public function __construct(bool $useDefaultRoutes = true)
    {
        if ($useDefaultRoutes) {
            $this->defaults = [
                new Route('*', '/(?:{controller}(?:/{action:\w+}(?:/{params})?)?)?')
            ];
        }
    }

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

    public function setAreas(?array $areas = null): static
    {
        if ($areas === null) {
            $areas = [];
            foreach (glob($this->alias->resolve('@app/Areas/*'), GLOB_ONLYDIR) as $dir) {
                $dir = substr($dir, strrpos($dir, '/') + 1);
                if (preg_match('#^[A-Z]\w+$#', $dir)) {
                    $areas[] = $dir;
                }
            }
        }

        $this->areas = $areas;

        return $this;
    }

    public function getAreas(): array
    {
        return $this->areas;
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
        $pattern .= '(/{params:[-\w]+})?';
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

    protected function matchDefaultRoutes(string $uri, string $method): ?array
    {
        $handledUri = $uri;

        $area = null;
        if ($handledUri !== '/' && $this->areas) {
            if (($pos = strpos($handledUri, '/', 1)) !== false) {
                $area = Str::pascalize(substr($handledUri, 1, $pos - 1));
                if (in_array($area, $this->areas, true)) {
                    $handledUri = substr($handledUri, $pos);
                } else {
                    $area = null;
                }
            } else {
                $area = Str::pascalize(substr($handledUri, 1));
                if (in_array($area, $this->areas, true)) {
                    $handledUri = '/';
                } else {
                    $area = null;
                }
            }
        }

        $handledUri = $handledUri === '/' ? '/' : rtrim($handledUri, '/');

        for ($i = count($this->defaults) - 1; $i >= 0; $i--) {
            $route = $this->defaults[$i];
            if (($parts = $route->match($handledUri, $method)) !== null) {
                if ($area !== null) {
                    $parts['area'] = $area;
                }
                return $parts;
            }
        }

        return null;
    }

    public function match(?string $uri = null, ?string $method = null): bool
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        $this->eventDispatcher->dispatch(new RouterRouting($this));

        $uri = $uri ?: $this->getRewriteUri();

        if ($method === null) {
            $method = $this->request->method();
        }

        $context->controller = null;
        $context->action = null;
        $context->params = [];

        $context->matched = false;

        if (($prefix = $this->getPrefix()) !== '') {
            if (str_starts_with($uri, $prefix)) {
                $handledUri = substr($uri, strlen($prefix)) ?: '/';
            } else {
                $handledUri = false;
            }
        } else {
            $handledUri = $uri;
        }

        $area = null;
        $routes = $this->literals;
        if ($handledUri === false) {
            $parts = null;
        } elseif (isset($routes[$method][$handledUri])) {
            $parts = $routes[$method][$handledUri]->match($handledUri, $method);
        } else {
            $parts = null;
            $routes = $this->regexes;
            for ($i = count($routes) - 1; $i >= 0; $i--) {
                $route = $routes[$i];
                if (($parts = $route->match($handledUri, $method)) !== null) {
                    if ($handledUri !== '/' && $this->areas) {
                        if (($pos = strpos($handledUri, '/', 1)) === false) {
                            $area = Str::pascalize(substr($handledUri, 1));
                        } else {
                            $area = Str::pascalize(substr($handledUri, 1, $pos - 1));
                        }

                        if (!in_array($area, $this->areas, true)) {
                            $area = null;
                        }
                    }
                    break;
                }
            }

            if ($parts === null) {
                $parts = $this->matchDefaultRoutes($handledUri, $method);
            }
        }

        if ($parts === null) {
            $this->eventDispatcher->dispatch(new RouterRouted($this));

            return false;
        }

        $context->matched = true;

        if ($area) {
            $context->area = $area;
        } elseif (isset($parts['area'])) {
            $context->area = $parts['area'];
        }

        $context->controller = $parts['controller'];
        $context->action = $parts['action'];
        $context->params = $parts['params'] ?? [];

        $this->eventDispatcher->dispatch(new RouterRouted($this));

        return $context->matched;
    }

    public function getArea(): ?string
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        return $context->area;
    }

    public function setArea(string $area): static
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        $context->area = $area;

        return $this;
    }

    public function getController(): ?string
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        return $context->controller;
    }

    public function setController(string $controller): static
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        $context->controller = $controller;

        return $this;
    }

    public function getAction(): ?string
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        return $context->action;
    }

    public function setAction(string $action): static
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        $context->action = $action;

        return $this;
    }

    public function getParams(): array
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        return $context->params;
    }

    public function setParams(array $params): static
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        $context->params = $params;

        return $this;
    }

    public function wasMatched(): bool
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        return $context->matched;
    }

    public function setMatched(bool $matched): static
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

        $context->matched = $matched;

        return $this;
    }

    public function createUrl(string|array $args, bool|string $scheme = false): string
    {
        /** @var RouterContext $context */
        $context = $this->getContext();

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

        $area = $context->area;
        $controller = $context->controller;
        if ($path === '') {
            $action = $context->action;
            $ca = $area ? "$area/$controller/$action" : "$controller/$action";
        } elseif (!str_contains($path, '/')) {
            $ca = $area ? "$area/$controller/$path" : "$controller/$path";
        } elseif ($path === '/') {
            $ca = '';
        } elseif ($path[0] === '/') {
            $ca = substr($path, 1);
        } elseif ($area) {
            $ca = $area . '/' . $path;
        } else {
            $ca = rtrim($path, '/');
        }

        while (($pos = strrpos($ca, '/index')) !== false && $pos + 6 === strlen($ca)) {
            $ca = substr($ca, 0, $pos);
        }

        $url = $this->getPrefix() . '/' . lcfirst($ca);
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }

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
