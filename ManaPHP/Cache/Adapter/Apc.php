<?php

namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache\AdapterInterface;
use ManaPHP\Component;
use ManaPHP\Cache\Adapter\Apc\Exception as ApcException;

class Apc extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'manaphp:cache:';

    /**
     * Apc constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Cache\Adapter\Exception
     */
    public function __construct($options = [])
    {
        if (!function_exists('apc_exists')) {
            throw new ApcException('apc extension is not loaded: http://pecl.php.net/package/APCu'/**m097f29c9069e20c50*/);
        }

        if (!ini_get('apc.enable_cli')) {
            throw new ApcException('apc.enable_cli=0, please enable it.'/**m03cb046c90f464b79*/);
        }

        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['prefix' => $options];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    public function exists($key)
    {
        return apc_exists($this->_prefix . $key);
    }

    public function get($key)
    {
        return apc_fetch($this->_prefix . $key);
    }

    public function set($key, $value, $ttl)
    {
        $r = apc_store($this->_prefix . $key, $value, $ttl);
        if (!$r) {
            throw new ApcException('apc_store failed for `:key` key'/**m044d8697223644728*/, ['key' => $key]);
        }
    }

    public function delete($key)
    {
        apc_delete($this->_prefix . $key);
    }
}