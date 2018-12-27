<?php

namespace ManaPHP;

use ManaPHP\Exception\MisuseException;

class IpcCache extends Component implements IpcCacheInterface
{
    /**
     * @var bool
     */
    protected $_enabled;

    /**
     * @var string
     */
    protected $_prefix;

    /**
     * @var array
     */
    protected $_cache;

    /**
     * IpcCache constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_enabled = function_exists('apcu_fetch');

        if (isset($options['enabled'])) {
            $this->_enabled = $options['enabled'] && function_exists('apcu_fetch');
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_cache = null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     */
    public function set($key, $value, $ttl)
    {
        if ($value === false) {
            throw new MisuseException(['value of `:key` key can not be false', 'key' => $key]);
        }

        if ($ttl < 0) {
            $this->_cache[$key] = $value;
        } else {
            if ($this->_enabled) {
                apcu_store($this->_prefix ? ($this->_prefix . $key) : $key, $value, $ttl);
            }
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        if ($this->_cache !== null && array_key_exists($key, $this->_cache)) {
            return $this->_cache[$key];
        } else {
            return $this->_enabled ? apcu_fetch($this->_prefix ? ($this->_prefix . $key) : $key) : false;
        }
    }
}