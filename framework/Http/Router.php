<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Router\Route;
use ManaPHP\Http\Router\RouteInterface;

/**
 * @property-read \ManaPHP\AliasInterface        $alias
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\RouterContext    $context
 */
class Router extends Component implements RouterInterface
{
    protected bool $case_sensitive = true;
    protected string $prefix = '';
    protected array $areas = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[]
     */
    protected array $defaults = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[][]
     */
    protected array $simples = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[]
     */
    protected array $regexes = [];

    public function __construct(bool $useDefaultRoutes = true)
    {
        if ($useDefaultRoutes) {
            $this->defaults = [
                new Route('/(?:{controller}(?:/{action:\w+}(?:/{params})?)?)?')
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
        return $this->prefix;
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

    protected function addRoute(string $pattern, string|array $paths = [], string|array $methods = []): RouteInterface
    {
        $route = new Route($pattern, $paths, $methods, $this->case_sensitive);
        if (!is_array($methods) && strpbrk($pattern, ':{') === false) {
            $this->simples[$methods][$pattern] = $route;
        } else {
            $this->regexes[] = $route;
        }

        return $route;
    }

    public function add(string $pattern, string|array $paths = [], string|array $methods = []): RouteInterface
    {
        return $this->addRoute($pattern, $paths, $methods);
    }

    public function addGet(string $pattern, string|array $paths = []): RouteInterface
    {
        return $this->addRoute($pattern, $paths, 'GET');
    }

    public function addPost(string $pattern, string|array $paths = []): RouteInterface
    {
        return $this->addRoute($pattern, $paths, 'POST');
    }

    public function addPut(string $pattern, string|array $paths = []): RouteInterface
    {
        return $this->addRoute($pattern, $paths, 'PUT');
    }

    public function addPatch(string $pattern, string|array $paths = []): RouteInterface
    {
        return $this->addRoute($pattern, $paths, 'PATCH');
    }

    public function addDelete(string $pattern, string|array $paths = []): RouteInterface
    {
        return $this->addRoute($pattern, $paths, 'DELETE');
    }

    public function addHead(string $pattern, string|array $paths = []): RouteInterface
    {
        return $this->addRoute($pattern, $paths, 'HEAD');
    }

    public function addRest(string $pattern, ?string $controller = null): RouteInterface
    {
        $pattern .= '(/{params:[-\w]+})?';

        if ($controller === null) {
            if (str_contains($pattern, '/:controller')) {
                return $this->addRoute($pattern, [], 'REST');
            }

            if (!preg_match('#/(\w+)$#', $pattern, $match)) {
                throw new MisuseException('must provide paths');
            }
            $controller = Str::singular($match[1]);
        }

        return $this->addRoute($pattern, $controller, 'REST');
    }

    public function getRewriteUri(): string
    {
        if (($url = $this->request->get('_url', '')) === '') {
            $request_uri = $this->request->getServer('REQUEST_URI', '/');
            $pos = strpos($request_uri, '?');
            $url = $pos === false ? $request_uri : substr($request_uri, 0, $pos);
        }

        $url = rtrim($url, '/') ?: '/';

        if ($url[0] !== '/') {
            $url = parse_url($url, PHP_URL_PATH);
        }

        if ($this->prefix === '') {
            return $url;
        } elseif (str_starts_with($url, $this->prefix)) {
            $url = substr($url, strlen($this->prefix));
            return $url === '' ? '/' : $url;
        } else {
            return $url;
        }
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
        $context = $this->context;

        $this->fireEvent('request:routing');

        $uri = $uri ?: $this->getRewriteUri();

        if ($method === null) {
            $method = $this->request->getMethod();
        }

        $context->controller = null;
        $context->action = null;
        $context->params = [];

        $context->matched = false;

        if ($this->prefix) {
            if (str_starts_with($uri, $this->prefix)) {
                $handledUri = substr($uri, strlen($this->prefix)) ?: '/';
            } else {
                $handledUri = false;
            }
        } else {
            $handledUri = $uri;
        }

        $area = null;
        $routes = $this->simples;
        if ($handledUri === false) {
            $parts = null;
        } elseif (isset($routes[$method][$handledUri])) {
            $parts = $routes[$method][$handledUri]->match($handledUri, $method);
        } elseif (isset($routes[''][$handledUri])) {
            $parts = $routes[''][$handledUri]->match($handledUri, $method);
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
            $this->fireEvent('request:routed');

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

        $this->fireEvent('request:routed');

        return $context->matched;
    }

    public function getArea(): ?string
    {
        return $this->context->area;
    }

    public function setArea(string $area): static
    {
        $this->context->area = $area;

        return $this;
    }

    public function getController(): ?string
    {
        return $this->context->controller;
    }

    public function setController(string $controller): static
    {
        $this->context->controller = $controller;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->context->action;
    }

    public function setAction(string $action): static
    {
        $this->context->action = $action;

        return $this;
    }

    public function getParams(): array
    {
        return $this->context->params;
    }

    public function setParams(array $params): static
    {
        $this->context->params = $params;

        return $this;
    }

    public function wasMatched(): bool
    {
        return $this->context->matched;
    }

    public function setMatched(bool $matched): static
    {
        $this->context->matched = $matched;

        return $this;
    }

    public function createUrl(string|array $args, bool|string $scheme = false): string
    {
        $context = $this->context;

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
            $ca = $area ? "{$area}/{$controller}/$action" : "{$controller}/$action";
        } elseif (!str_contains($path, '/')) {
            $ca = $area ? "{$area}/{$controller}/$path" : "{$controller}/$path";
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

        $url = $this->prefix . '/' . lcfirst($ca);
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
                $scheme = $this->request->getScheme();
            }
            return ($scheme === '//' ? '//' : "$scheme://") . $this->request->getServer('HTTP_HOST') . $url;
        } else {
            return $url;
        }
    }
}
