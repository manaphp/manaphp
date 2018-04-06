<?php

namespace ManaPHP;

use ManaPHP\Cache\Exception as CacheException;
use ManaPHP\Component\ScopedCloneableInterface;
use ManaPHP\Exception\InvalidValueException;

/**
 * Class ManaPHP\Cache
 *
 * @package cache
 */
class Cache extends Component implements CacheInterface, ScopedCloneableInterface
{
    /**
     * @var string|\ManaPHP\Cache\EngineInterface
     */
    protected $_engine;

    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * Cache constructor.
     *
     * @param string|array|\ManaPHP\Cache\EngineInterface $options
     */
    public function __construct($options = 'ManaPHP\Cache\Engine\File')
    {
        if (is_string($options) || is_object($options)) {
            $options = ['engine' => $options];
        }

        $this->_engine = $options['engine'];

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @return \ManaPHP\Cache\EngineInterface
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
     * @param string $key
     *
     * @return mixed|false
     * @throws \ManaPHP\Cache\Exception
     */
    public function get($key)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        if (($data = $engine->get($this->_prefix . $key)) === false) {
            $this->fireEvent('cache:miss', ['key' => $this->_prefix . $key]);
            return false;
        }

        $this->fireEvent('cache:hit', ['key' => $this->_prefix . $key]);

        if ($data[0] !== '{' && $data[0] !== '[') {
            return $data;
        }

        $json = json_decode($data, true);
        if ($json === null) {
            throw new CacheException([
                '`:key` key cache value json_encode failed: `:code` `:message`',
                'key' => $key,
                'code' => json_last_error(),
                'message' => json_last_error_msg()
            ]);
        }

        if (count($json) === 1 && key($json) === '_wrapper_') {
            return $json['_wrapper_'];
        } else {
            return $json;
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl)
    {
        if ($value === false) {
            throw new InvalidValueException(['`:key` key cache value can not `false` boolean value', 'key' => $key]);
        } elseif (is_scalar($value) || $value === null) {
            if (is_string($value) && $value !== '' && $value[0] !== '{' && $value[0] !== '[') {
                $data = $value;
            } else {
                $data = json_encode(['_wrapper_' => $value], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        } elseif (is_array($value) && isset($value['_wrapper_'])) {
            $data = json_encode(['_wrapper_' => $value], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $data = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($data === false) {
            throw new RuntimeException([
                '`:key` key cache value json_encode failed: `:code` `:message`',
                'key' => $key,
                'code' => json_last_error(),
                'message' => json_last_error_msg()
            ]);
        }

        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        $engine->set($this->_prefix . $key, $data, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        $engine->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        return $engine->exists($this->_prefix . $key);
    }

    /**
     * @param string   $key
     * @param int      $ttl
     * @param callable $callback
     *
     * @return mixed
     * @throws \ManaPHP\Cache\Exception
     */
    public function remember($key, $ttl, $callback)
    {
        $r = $this->get($key);
        if ($r === false) {
            $r = $callback();
            $this->set($key, $r, $ttl);
        }

        return $r;
    }

    /**
     * @param \ManaPHP\Component $scope
     *
     * @return static
     */
    public function getScopedClone($scope)
    {
        if (!is_object($this->_engine)) {
            $this->_getEngine();
        }

        $cloned = clone $this;
        $cloned->_prefix = ($this->_prefix ? $this->_prefix . ':' : '') . $scope->getComponentName($this) . ':';

        return $cloned;
    }
}