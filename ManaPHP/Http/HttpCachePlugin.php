<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Plugin;

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
     * @param EventArgs $eventArgs
     *
     * @return void
     * @throws MisuseException
     */
    public function onResponseSending(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\ResponseContext $responseContext */
        $responseContext = $eventArgs->data['context'];
        if ($responseContext->status_code !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
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
                    if (isset($responseContext->headers['ETag'])) {
                        $etag = $responseContext->headers['ETag'];
                    } else {
                        $etag = md5($responseContext->content);
                        $responseContext->headers['ETag'] = $etag;
                    }

                    $if_none_match = $this->request->getIfNoneMatch();
                    if ($if_none_match === $etag) {
                        $responseContext->status_code = 304;
                        $responseContext->status_text = 'Not Modified';
                        return;
                    }
                } else {
                    $responseContext->headers['Cache-Control'] = $v;
                }
            } elseif ($k === 'max-age') {
                $responseContext->headers['Cache-Control'] = "max-age=$v";
            } elseif ($k === 'Cache-Control' || $k === 'cache-control') {
                $responseContext->headers['Cache-Control'] = $v;
            } else {
                throw new MisuseException(['not support `:key`', 'key' => $k]);
            }
        }
    }
}
