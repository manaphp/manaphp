<?php
namespace ManaPHP;

use ManaPHP\Cache\Exception as CacheException;
use ManaPHP\Component\ScopedCloneableInterface;

/**
 * Class ManaPHP\Cache
 *
 * @package cache
 */
class Cache extends Component implements CacheInterface, ScopedCloneableInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var \ManaPHP\Cache\EngineInterface
     */
    protected $_engine;

    /**
     * Cache constructor.
     *
     * @param string|array|\ManaPHP\Cache\EngineInterface $options
     */
    public function __construct($options = [])
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
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (!is_object($this->_engine)) {
            $this->_engine = $this->_dependencyInjector->getShared($this->_engine);
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        if (($data = $this->_engine->get($this->_prefix . $key)) === false) {
            $this->fireEvent('cache:miss', ['key' => $this->_prefix . $key]);
            return false;
        }

        $this->fireEvent('cache:hit', ['key' => $this->_prefix . $key]);

        if ($data[0] !== '{' && $data[0] !== '[') {
            return $data;
        }

        $json = json_decode($data, true);
        if ($json === null) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new CacheException('`:key` key cache value json_encode failed: `:code` `:message`',
                ['key' => $key, 'code' => json_last_error(), 'message' => json_last_error_msg()]);
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
     * @throws \ManaPHP\Cache\Exception
     */
    public function set($key, $value, $ttl)
    {
        if ($value === false) {
            throw new CacheException('`:key` key cache value can not `false` boolean value', ['key' => $key]);
        } elseif (is_scalar($value) || $value === null) {
            if (is_string($value) && $value !== '' && $value[0] === '{' && $value[0] === '[') {
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
            throw new CacheException('`:key` key cache value json_encode failed: `:code` `:message`',
                ['key' => $key, 'code' => json_last_error(), 'message' => json_last_error_msg()]);
        }

        $this->_engine->set($this->_prefix . $key, $data, $ttl);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->_engine->delete($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->_engine->exists($this->_prefix . $key);
    }

    /**
     * @param string   $key
     * @param int      $ttl
     * @param callable $callback
     *
     * @return mixed
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
        $cloned = clone $this;
        $cloned->_prefix = ($this->_prefix ? $this->_prefix . ':' : '') . $scope->getComponentName($this) . ':';

        return $cloned;
    }
}