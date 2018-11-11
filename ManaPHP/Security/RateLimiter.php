<?php
namespace ManaPHP\Security;

use ManaPHP\Component;

/**
 * Class ManaPHP\Security\RateLimiter
 *
 * @package rateLimiter
 *
 * @property-read \ManaPHP\DispatcherInterface   $dispatcher
 * @property-read \ManaPHP\IdentityInterface     $identity
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class RateLimiter extends Component implements RateLimiterInterface
{
    /**
     * @var string|\ManaPHP\Security\RateLimiter\EngineInterface
     */
    protected $_engine;

    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * RateLimiter constructor.
     *
     * @param string|array|\ManaPHP\Security\RateLimiter\EngineInterface $options
     */
    public function __construct($options = 'ManaPHP\Security\RateLimiter\Engine\Redis')
    {
        if (is_string($options) || is_object($options)) {
            $this->_engine = $options;
        } else {
            if (isset($options['engine'])) {
                $this->_engine = $options['engine'];
            }

            if (isset($options['prefix'])) {
                $this->_prefix = $options['prefix'];
            }
        }
    }

    /**
     * @return \ManaPHP\Security\RateLimiter\EngineInterface
     */
    protected function _getEngine()
    {
        if (is_string($this->_engine)) {
            return $this->_engine = $this->_di->getShared($this->_engine);
        } else {
            return $this->_engine = $this->_di->getInstance($this->_engine);
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @param int    $times
     * @param int    $duration
     *
     * @return int
     */
    public function limit($type, $id, $times, $duration)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        if (($r = $times - $engine->check($this->_prefix . $type, $id, $duration)) < 0) {
            $this->fireEvent('rateLimiter:exceed', ['type' => $type, 'id' => $id, 'left' => $r]);
        }

        return $r;
    }

    /**
     * @param int $times
     * @param int $duration
     *
     * @return int
     */
    public function limitIp($times, $duration)
    {
        return $this->limit('ip', $this->request->getClientIp(), $times, $duration);
    }

    /**
     * @param int $times
     * @param int $duration
     *
     * @return int
     */
    public function limitUser($times, $duration)
    {
        $userName = $this->identity->getName('');
        if ($userName) {
            return $this->limit('user', $userName, $times, $duration);
        } else {
            return 1;
        }
    }
}