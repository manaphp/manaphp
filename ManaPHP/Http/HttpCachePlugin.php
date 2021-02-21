<?php

namespace ManaPHP\Http;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Plugin;

/**
 * @property-read \ManaPHP\Http\RequestInterface    $request
 * @property-read \ManaPHP\Http\ResponseInterface   $response
 * @property-read \ManaPHP\Http\DispatcherInterface $dispatcher
 */
class HttpCachePlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = (bool)$options['enabled'];
        }

        if ($this->enabled) {
            $this->attachEvent('response:sending', [$this, 'onResponseSending']);
        }
    }

    /**
     * @return void
     * @throws MisuseException
     */
    public function onResponseSending()
    {
        if ($this->response->getStatusCode() !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        /** @var \ManaPHP\Http\Controller $controller */
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
