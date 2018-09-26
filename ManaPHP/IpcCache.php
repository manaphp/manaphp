<?php

namespace ManaPHP;

class IpcCache implements IpcCacheInterface
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

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     */
    public function set($key, $value, $ttl)
    {
        if ($this->_enabled) {
            apcu_store($this->_prefix ? ($this->_prefix . $key) : $key, $value, $ttl);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        if ($this->_enabled) {
            return apcu_fetch($this->_prefix ? ($this->_prefix . $key) : $key);
        } else {
            return false;
        }
    }
}