<?php

namespace ManaPHP\Ipc\Cache\Adapter;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Ipc\CacheInterface;

class Apcu extends Component implements CacheInterface
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
     * @var bool
     */
    protected $_is_cli;

    /**
     * Apcu constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_enabled = function_exists('apcu_fetch');

        if (isset($options['enabled'])) {
            $this->_enabled = $options['enabled'] && $this->_enabled;
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        $this->_is_cli = (PHP_SAPI === 'cli');
    }

    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_cache = null;
    }

    public function setDi($di)
    {
        parent::setDi($di);
        if (!$this->_enabled) {
            $this->logger->info('APCu needs enabling for the cli via apc.enable_cli=1 or apcu.enable_cli=1', 'ipcCache.enabled');
        }
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

        if ($ttl === 0) {
            $this->_cache[$key] = $value;
        } elseif ($this->_enabled) {
            apcu_store($this->_prefix ? ($this->_prefix . $key) : $key, $this->_is_cli ? [time() + $ttl, $value] : $value, $ttl);
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
        } elseif ($this->_enabled) {
            if ($this->_is_cli) {
                if (($r = apcu_fetch($this->_prefix ? ($this->_prefix . $key) : $key)) === false) {
                    return false;
                }
                return time() <= $r[0] ? $r[1] : false;
            } else {
                return apcu_fetch($this->_prefix ? ($this->_prefix . $key) : $key);
            }
        } else {
            return false;
        }
    }
}