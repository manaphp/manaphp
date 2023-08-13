<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\RespondingFilterInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;

class HttpCacheFilter extends Filter implements RespondingFilterInterface
{
    #[Inject]
    protected RequestInterface $request;
    #[Inject]
    protected ResponseInterface $response;
    #[Inject]
    protected DispatcherInterface $dispatcher;

    public function onResponding(): void
    {
        if ($this->response->getStatusCode() !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        $controller = $this->dispatcher->getControllerInstance();
        $action = $this->dispatcher->getAction();

        if ($controller === null || $action === null) {
            return;
        }

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
