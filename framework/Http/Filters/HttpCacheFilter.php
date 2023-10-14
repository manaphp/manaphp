<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filters;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\Controller\Attribute\HttpCache;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestResponsing;
use ReflectionMethod;

class HttpCacheFilter
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    protected array $httpCaches = [];

    protected function getHttpCache(object $controller, string $action): HttpCache|false
    {
        $rMethod = new ReflectionMethod($controller, $action . 'Action');

        if (($attributes = $rMethod->getAttributes(HttpCache::class)) !== []) {
            return $attributes[0]->newInstance();
        } else {
            return false;
        }
    }

    public function onResponding(#[Event] RequestResponsing $event): void
    {
        if ($this->response->getStatusCode() !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        $controller = $this->dispatcher->getControllerInstance();
        $action = $this->dispatcher->getAction();

        if ($controller === null || $action === null) {
            return;
        }

        $key = $controller::class . '::' . $action;
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

                    $if_none_match = $this->request->getIfNoneMatch();
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
