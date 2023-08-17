<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\EventArgs;
use ManaPHP\Exception\TooManyRequestsException;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\ValidatingFilterInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Redis\RedisCacheInterface;

class RateLimitFilter extends Filter implements ValidatingFilterInterface
{
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected IdentityInterface $identity;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected RedisCacheInterface $redisCache;

    #[Value] protected ?string $prefix;
    #[Value] protected string $limits = '60/m';

    public function onValidating(EventArgs $eventArgs): void
    {
        /** @var \ManaPHP\Http\DispatcherInterface $dispatcher */
        $dispatcher = $eventArgs->source;
        /** @var \ManaPHP\Rest\Controller $controller */
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];
        $rateLimit = $controller->getRateLimit();

        $limits = (array)($rateLimit[$action] ?? $rateLimit['*'] ?? $this->limits);

        if (($burst = $limits['burst'] ?? null) !== null) {
            unset($burst['burst']);
        }

        $uid = $this->identity->getName('') ?: $this->request->getClientIp();
        $prefix = ($this->prefix ?? sprintf("cache:%s:rateLimitPlugin:", $this->config->get('id')))
            . $dispatcher->getPath() . ':' . $uid . ':';

        foreach ($limits as $k => $v) {
            if ($pos = strpos($v, '/')) {
                $limit = (int)substr($v, 0, $pos);
                $right = substr($v, $pos + 1);
                $period = seconds(strlen($right) === 1 ? "1$right" : $right);
            } else {
                $limit = (int)$v;
                $period = 60;
            }

            $key = $prefix . $period;

            if ($k === 0 && $burst !== null) {
                if (($used = $this->redisCache->get($key)) === false) {
                    $this->redisCache->setex($key, $period, '1');
                } elseif ($used >= $limit) {
                    throw new TooManyRequestsException();
                } else {
                    if (($left = $this->redisCache->pttl($key)) <= 0) {
                        $this->redisCache->setex($key, $period, '1');
                    } else {
                        $ideal = (int)(($period - $left / 1000) * $limit / $period) + 1;
                        if ($used < $ideal) {
                            $diff = $ideal - $used;
                            if ($this->redisCache->incrBy($key, $diff) === $diff) {
                                $this->redisCache->setex($key, $period, '1');
                            }
                        } elseif ($used > $ideal + $burst) {
                            throw new TooManyRequestsException();
                        } else {
                            if ($this->redisCache->incr($key) === 1) {
                                $this->redisCache->expire($key, $period);
                            }
                        }
                    }
                }
            } else {
                if (($count = $this->redisCache->incr($key)) === 1) {
                    $this->redisCache->expire($key, $period);
                } else {
                    if ($count > $limit) {
                        throw new TooManyRequestsException();
                    }
                }
            }
        }
    }
}
