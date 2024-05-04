<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Controller\Attribute\HttpCache as HttpCacheAttribute;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestResponding;
use ReflectionMethod;
use function in_array;
use function is_int;

class HttpCacheMiddleware
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    protected array $httpCaches = [];

    protected function getHttpCache(string $controller, string $action): HttpCacheAttribute|false
    {
        $rMethod = new ReflectionMethod($controller, $action . 'Action');

        if (($attributes = $rMethod->getAttributes(HttpCacheAttribute::class)) !== []) {
            return $attributes[0]->newInstance();
        } else {
            return false;
        }
    }

    public function onResponding(#[Event] RequestResponding $event): void
    {
        SuppressWarnings::unused($event);

        if ($this->response->getStatusCode() !== 200 || !in_array($this->request->method(), ['GET', 'HEAD'], true)) {
            return;
        }

        if (($controller = $this->dispatcher->getController()) === null) {
            return;
        }
        $action = $this->dispatcher->getAction();

        $key = $controller . '::' . $action;
        if (($httpCache = $this->httpCaches[$key] ?? null) === null) {
            $httpCache = $this->httpCaches[$key] = $this->getHttpCache($controller, $action);
        }

        if ($httpCache === false) {
            return;
        }

        foreach ($httpCache->headers as $k => $v) {
            if (is_int($k)) {
                if ($v === 'etag' || $v === 'ETag') {
                    if (($etag = $this->response->getHeader('ETag', '')) === '') {
                        $etag = md5($this->response->getContent());
                        $this->response->setETag($etag);
                    }

                    $if_none_match = $this->request->header('if-none-match');
                    if ($if_none_match === $etag) {
                        $this->response->setNotModified();
                        return;
                    }
                } else {
                    $this->response->setCacheControl($v);
                }
            } elseif ($k === 'max-age') {
                $this->response->setCacheControl("max-age=$v");
            } elseif ($k === 'Cache-Control' || $k === 'cache-control') {
                $this->response->setCacheControl($v);
            } else {
                throw new MisuseException(['not support `{key}`', 'key' => $k]);
            }
        }
    }
}
