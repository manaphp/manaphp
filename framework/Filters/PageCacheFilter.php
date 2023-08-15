<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use Closure;
use ManaPHP\ConfigInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\ReadyFilterInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Redis\RedisCacheInterface;

class PageCacheFilter extends Filter implements ReadyFilterInterface
{
    use ContextTrait;

    #[Inject] protected ConfigInterface $config;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected RedisCacheInterface $redisCache;

    protected string $prefix;

    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? sprintf("cache:%s:pageCachePlugin:", $this->config->get('id'));
    }

    public function onReady(EventArgs $eventArgs): void
    {
        if (!in_array($this->request->getMethod(), ['GET', 'POST', 'HEAD'], true)) {
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

        /** @var PageCacheFilterContext $context */
        $context = $this->getContext();

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

    public function onResponding(): void
    {
        /** @var PageCacheFilterContext $context */
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
