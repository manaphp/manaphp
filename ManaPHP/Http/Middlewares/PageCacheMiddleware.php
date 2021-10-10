<?php

namespace ManaPHP\Http\Middlewares;

use Closure;
use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Helper\Reflection;
use ManaPHP\Http\Middleware;
use ManaPHP\Mvc\Controller as MvcController;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class PageCacheMiddlewareContext
{
    public $ttl;
    public $key;
    public $if_none_match;
    public $cache_used;
}

/**
 * @property-read \ManaPHP\ConfigInterface                             $config
 * @property-read \ManaPHP\Http\RequestInterface                       $request
 * @property-read \ManaPHP\Http\ResponseInterface                      $response
 * @property-read \Redis|\ManaPHP\Data\RedisInterface                  $redisCache
 * @property-read \ManaPHP\Http\Middlewares\PageCacheMiddlewareContext $context
 */
class PageCacheMiddleware extends Middleware
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->prefix = $options['prefix'] ?? sprintf("cache:%s:pageCachePlugin:", $this->config->get('id'));
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     * @throws AbortException
     * @throws MissingFieldException
     */
    public function onReady(EventArgs $eventArgs)
    {
        if (!in_array($this->request->getMethod(), ['GET', 'POST', 'HEAD'])) {
            return;
        }

        /** @var \ManaPHP\Http\DispatcherInterface $dispatcher */
        /** @var \ManaPHP\Http\Controller $controller */
        $dispatcher = $eventArgs->source;
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];

        $pageCache = $controller->getPageCache();
        if ($pageCache === [] || ($pageCache = $pageCache[$action] ?? false) === false) {
            return;
        }

        $context = $this->context;

        $key = null;
        if (is_int($pageCache)) {
            $context->ttl = $pageCache;
        } elseif (is_array($pageCache)) {
            if (!isset($pageCache['ttl'])) {
                throw new MissingFieldException('ttl');
            }
            $context->ttl = $pageCache['ttl'];

            if (isset($pageCache['key'])) {
                $key = $pageCache['key'];

                if ($key instanceof Closure) {
                    $key = $key();
                } elseif (is_array($key)) {
                    $params = [];
                    foreach ((array)$pageCache['key'] as $k => $v) {
                        if (is_int($k)) {
                            $param_name = $v;
                            $param_value = input($param_name, '');
                        } else {
                            $param_name = $k;
                            $param_value = $v;
                        }

                        if ($param_value !== '') {
                            $params[$param_name] = $param_value;
                        }
                    }

                    ksort($params);
                    $key = http_build_query($params);
                }
            }
        }

        if ($key === null) {
            $params = [];
            foreach ($this->request->get() as $name => $value) {
                if ($name !== '_url' && $value !== '') {
                    $params[$name] = $value;
                }
            }

            ksort($params);
            $key = http_build_query($params);
        }

        if ($key === '') {
            $context->key = $this->prefix . $dispatcher->getPath();
        } else {
            $context->key = $this->prefix . $dispatcher->getPath() . ':' . $key;
        }

        if (Reflection::isInstanceOf($controller, MvcController::class) && $this->request->isAjax()) {
            $context->key .= ':ajax';
        }

        $context->if_none_match = $this->request->getIfNoneMatch();

        if (($etag = $this->redisCache->hGet($context->key, 'etag')) === false) {
            return;
        }

        if ($etag === $context->if_none_match) {
            $this->response->setNotModified();
            throw new AbortException();
        }

        if (!$cache = $this->redisCache->hGetAll($context->key)) {
            return;
        }

        $this->response->setETag($cache['etag']);
        $this->response->setMaxAge(max($this->redisCache->ttl($context->key), 1));

        if (isset($cache['content-type'])) {
            $this->response->setContentType($cache['content-type']);
        }

        if (str_contains($this->request->getServer('HTTP_ACCEPT_ENCODING'), 'gzip')) {
            $this->response->setHeader('Content-Encoding', 'gzip');
            $this->response->setContent($cache['content']);
        } else {
            $this->response->setContent(gzdecode($cache['content']));
        }
        $context->cache_used = true;

        throw new AbortException();
    }

    /**
     * @return void
     */
    public function onResponding()
    {
        $context = $this->context;

        if ($context->cache_used === true || $context->ttl === null || $context->ttl <= 0) {
            return;
        }

        if ($this->response->getStatusCode() !== 200) {
            return;
        }

        $content = $this->response->getContent();
        $etag = md5($content);

        $this->redisCache->hMSet(
            $context->key, [
                'ttl'          => $context->ttl,
                'etag'         => $etag,
                'content-type' => $this->response->getContentType(),
                'content'      => gzencode($content)
            ]
        );
        $this->redisCache->expire($context->key, $context->ttl);

        if ($context->if_none_match === $etag) {
            $this->response->setNotModified();
        } else {
            $this->response->setMaxAge($context->ttl);
            $this->response->setETag($etag);
        }
    }
}
