<?php
namespace ManaPHP\Plugins;

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
    protected $_action_limit = '60/m';

    /**
     * RateLimitPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        }

        $this->_prefix = $options['prefix'] ?? "cache:{$this->configure->id}:rateLimitPlugin:";

        if (isset($options['action_limit'])) {
            $this->_action_limit = $options['action_limit'];
        }

        if ($this->_enabled) {
            $this->attachEvent('request:validate', [$this, 'onRequestValidate'], true);
        }
    }

    public function onRequestValidate(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\DispatcherInterface $dispatcher */
        $dispatcher = $eventArgs->source;
        /** @var \ManaPHP\Rest\Controller $controller */
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];
        $rateLimit = $controller->getRateLimit();

        $arl_list = (array)($rateLimit[$action] ?? $rateLimit['*'] ?? $this->_action_limit);
        $burst = $arl_list['burst'] ?? null;

        foreach ($arl_list as $k => $arl) {
            if (is_string($k)) {
                continue;
            }

            if ($pos = strpos($arl, '/')) {
                $limit = (int)substr($arl, 0, $pos);
                $right = substr($arl, $pos + 1);
                $period = seconds(strlen($right) === 1 ? "1$right" : $right);
            } else {
                $limit = (int)$arl;
                $period = 60;
            }

            $uid = $this->identity->getName('') ?: $this->request->getClientIp();
            $key = $this->_prefix . $dispatcher->getPath() . ':' . $uid . ':' . $period;

            if ($k === 0 && $burst !== null) {
                if (($used = $this->redis->get($key)) === false) {
                    $this->redis->setex($key, $period, '1');
                } elseif ($used >= $limit) {
                    throw new TooManyRequestsException();
                } else {
                    if (($left = $this->redis->ttl($key)) <= 0) {
                        $this->redis->setex($key, $period, '1');
                    } else {
                        $ideal = (int)(($period - $left) * $limit / $period) + 1;
                        if ($used < $ideal) {
                            $diff = $ideal - $used;
                            if ($this->redis->incrBy($key, $diff) === $diff) {
                                $this->redis->setex($key, $period, '1');
                            }
                        } elseif ($used > $ideal + $burst) {
                            throw new TooManyRequestsException();
                        } else {
                            if ($this->redis->incr($key) === 1) {
                                $this->redis->expire($key, $period);
                            }
                        }
                    }
                }
            } else {
                if (($count = $this->redis->incr($key)) === 1) {
                    $this->redis->expire($key, $period);
                } else {
                    if ($count > $limit) {
                        throw new TooManyRequestsException();
                    }
                }
            }
        }
    }
}
