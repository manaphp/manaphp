<?php

namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Plugin;

class HttpCachePlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * HttpCachePlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        }

        if ($this->_enabled) {
            $this->attachEvent('response:sending', [$this, 'onResponseSending']);
        }
    }

    public function onResponseSending(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\ResponseContext $response */
        $response = $eventArgs->data['response'];
        if ($response->status_code !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
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
                    if (isset($response->headers['ETag'])) {
                        $etag = $response->headers['ETag'];
                    } else {
                        $etag = strlen($response->content) . '-' . md5($response->content);
                        $response->headers['ETag'] = $etag;
                    }

                    $if_none_match = $this->request->getServer('HTTP_IF_NONE_MATCH');
                    if ($if_none_match === $etag) {
                        $response->status_code = 304;
                        $response->status_text = 'Not Modified';
                        return;
                    }
                } else {
                    $response->headers['Cache-Control'] = $v;
                }
            } elseif ($k === 'max-age') {
                $response->headers['Cache-Control'] = "max-age=$v";
            } elseif ($k === 'Cache-Control' || $k === 'cache-control') {
                $response->headers['Cache-Control'] = $v;
            } else {
                throw new MisuseException(['not support `:key`', 'key' => $k]);
            }
        }
    }
}
