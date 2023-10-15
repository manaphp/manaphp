<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filters;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Controller\Attribute\PageCache as PageCacheAttribute;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestReady;
use ManaPHP\Http\Server\Event\RequestResponsing;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Redis\RedisCacheInterface;
use ReflectionMethod;

class PageCache
{
    use ContextTrait;

    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RedisCacheInterface $redisCache;

    #[Config] protected string $app_id;

    protected string $prefix;

    protected array $pageCaches = [];

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? sprintf('cache:%s:pageCachePlugin:', $this->app_id);
    }

    protected function getPageCache(object $controller, string $action): PageCacheAttribute|false
    {
        $rMethod = new ReflectionMethod($controller, $action . 'Action');

        if (($attributes = $rMethod->getAttributes(PageCacheAttribute::class)) !== []) {
            return $attributes[0]->newInstance();
        } else {
            return false;
        }
    }

    public function onReady(#[Event] RequestReady $event): void
    {
        if (!in_array($this->request->getMethod(), ['GET', 'POST', 'HEAD'], true)) {
            return;
        }

        $dispatcher = $event->dispatcher;
        $controller = $event->controller;
        $action = $event->action;

        $key = $controller::class . '::' . $action;
        if (($pageCache = $this->pageCaches[$key] ?? null) === null) {
            $pageCache = $this->pageCaches[$key] = $this->getPageCache($controller, $action);
        }

        if ($pageCache === false) {
            return;
        }

        /** @var PageCacheContext $context */
        $context = $this->getContext();

        $context->ttl = $pageCache->ttl;

        $key = null;
        if ($pageCache->key !== null) {
            $key = $pageCache->key;
            if (is_array($key)) {
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

        if ($key === null) {
            $params = [];
            foreach ($this->request->all() as $name => $value) {
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

        if ($controller instanceof MvcController && $this->request->isAjax()) {
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

    public function onResponding(#[Event] RequestResponsing $event): void
    {
        /** @var PageCacheContext $context */
        $context = $this->getContext();

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
