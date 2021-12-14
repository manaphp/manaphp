<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\Middleware;

/**
 * @property-read \ManaPHP\Http\RequestInterface    $request
 * @property-read \ManaPHP\Http\ResponseInterface   $response
 * @property-read \ManaPHP\Http\DispatcherInterface $dispatcher
 */
class HttpCacheMiddleware extends Middleware
{
    public function onResponding(): void
    {
        if ($this->response->getStatusCode() !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        $controller = $this->dispatcher->getControllerInstance();
        $action = $this->dispatcher->getAction();

        $httpCache = $controller->getHttpCache();
        if ($httpCache === [] || ($httpCache = $httpCache[$action] ?? $httpCache['*'] ?? false) === false) {
            return;
        }

        foreach ((array)$httpCache as $k => $v) {
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
                throw new MisuseException(['not support `:key`', 'key' => $k]);
            }
        }
    }
}
