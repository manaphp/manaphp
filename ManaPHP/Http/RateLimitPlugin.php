<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\TooManyRequestsException;
use ManaPHP\Plugin;

class RateLimitPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var string
     */
    protected $_prefix;

    /**
     * @var string
     */
    protected $_limits = '60/m';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['redisCache'])) {
            $this->_injections['redisCache'] = $options['redisCache'];
        }

        if (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        }

        $this->_prefix = $options['prefix'] ?? "cache:{$this->configure->id}:rateLimitPlugin:";

        if (isset($options['limits'])) {
            $this->_limits = $options['limits'];
        }

        if ($this->_enabled) {
            $this->attachEvent('request:validate', [$this, 'onRequestValidate'], true);
        }
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     * @throws TooManyRequestsException
     * @throws \ManaPHP\Exception\InvalidValueException
     */
    public function onRequestValidate(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\DispatcherInterface $dispatcher */
        $dispatcher = $eventArgs->source;
        /** @var \ManaPHP\Rest\Controller $controller */
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];
        $rateLimit = $controller->getRateLimit();

        $limits = (array)($rateLimit[$action] ?? $rateLimit['*'] ?? $this->_limits);
        $burst = $limits['burst'] ?? null;

        foreach ($limits as $k => $v) {
            if (is_string($k)) {
                continue;
            }

            if ($pos = strpos($v, '/')) {
                $limit = (int)substr($v, 0, $pos);
                $right = substr($v, $pos + 1);
                $period = seconds(strlen($right) === 1 ? "1$right" : $right);
            } else {
                $limit = (int)$v;
                $period = 60;
            }

            $uid = $this->identity->getName('') ?: $this->request->getClientIp();
            $key = $this->_prefix . $dispatcher->getPath() . ':' . $uid . ':' . $period;

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
