<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\TooManyRequestsException;
use ManaPHP\Http\Controller\Attribute\RateLimit;
use ManaPHP\Http\Filter;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestValidating;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Redis\RedisCacheInterface;
use ReflectionClass;
use ReflectionMethod;

class RateLimitFilter extends Filter
{
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected IdentityInterface $identity;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected RedisCacheInterface $redisCache;

    #[Value] protected ?string $prefix;

    protected array $rateLimits = [];

    protected function getRateLimit($controller, $action): RateLimit|false
    {
        $rMethod = new ReflectionMethod($controller, $action . 'Action');
        if (($attributes = $rMethod->getAttributes(RateLimit::class)) !== []) {
            return $attributes[0]->newInstance();
        }

        $rClass = new ReflectionClass($controller);
        if (($attributes = $rClass->getAttributes(RateLimit::class)) !== []) {
            return $attributes[0]->newInstance();
        }

        return false;
    }

    public function onValidating(#[Event] RequestValidating $event): void
    {
        $dispatcher = $event->dispatcher;
        $controller = $event->controller::class;
        $action = $event->action;
        $key = "$controller::$action";
        if (($rateLimit = $this->rateLimits[$key] ?? null) === null) {
            $rateLimit = $this->rateLimits[$key] = $this->getRateLimit($controller, $action);
        }

        if ($rateLimit === false) {
            return;
        }

        $uid = $this->identity->getName('') ?: $this->request->getClientIp();
        $prefix = ($this->prefix ?? sprintf("cache:%s:rateLimitPlugin:", $this->config->get('id')))
            . $dispatcher->getPath() . ':' . $uid . ':';

        foreach ($rateLimit->limits as $k => $v) {
            $v = trim($v);
            if ($pos = strpos($v, '/')) {
                $limit = (int)substr($v, 0, $pos);
                $right = substr($v, $pos + 1);
                $period = seconds(strlen($right) === 1 ? "1$right" : $right);
            } else {
                $limit = (int)$v;
                $period = 60;
            }

            $key = $prefix . $period;

            if ($k === 0 && $rateLimit->burst !== null) {
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
                        } elseif ($used > $ideal + $rateLimit->burst) {
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
