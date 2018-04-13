<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;

/**
 * Class ManaPHP\Store
 *
 * @package store
 */
class Store extends Component implements StoreInterface
{
    /**
     * @var string|\ManaPHP\Store\EngineInterface
     */
    protected $_engine;

    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * Store constructor.
     *
     * @param string|array|\ManaPHP\Store\EngineInterface $options
     */
    public function __construct($options = 'ManaPHP\Store\Engine\File')
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
     * @return \ManaPHP\Store\EngineInterface
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
     */
    public function get($key)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        if (($data = $engine->get($this->_prefix . $key)) === false) {
            $this->fireEvent('store:miss', ['key' => $this->_prefix . $key]);
            return false;
        }

        $this->fireEvent('store:hit', ['key' => $this->_prefix . $key]);

        if ($data[0] !== '{' && $data[0] !== '[') {
            return $data;
        }

        $json = json_decode($data, true);
        if ($json === null) {
            throw new InvalidJsonException([
                '`:key` key store value json_decode failed: `:code` `:message`',
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
     *
     * @return void
     */
    public function set($key, $value)
    {
        if ($value === false) {
            throw new InvalidValueException(['`:key` key store value can not `false` boolean value', 'key' => $key]);
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
            throw new InvalidValueException([
                '`:key` key store value json_encode failed: `:code` `:message`',
                'key' => $key,
                'code' => json_last_error(),
                'message' => json_last_error_msg()
            ]);
        }

        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        $engine->set($this->_prefix . $key, $data);
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
     * @param callable $callback
     *
     * @return mixed
     */
    public function remember($key, $callback)
    {
        $r = $this->get($key);
        if ($r === false) {
            $r = $callback();
            $this->set($key, $r);
        }

        return $r;
    }
}