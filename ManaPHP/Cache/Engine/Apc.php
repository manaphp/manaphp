<?php

namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\Engine\Apc\Exception as ApcException;
use ManaPHP\Cache\EngineInterface;
use ManaPHP\Component;

/**
 * Class ManaPHP\Cache\Adapter\Apc
 *
 * @package cache\adapter
 */
class Apc extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * Apc constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Cache\Engine\Exception
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

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);
        if ($this->_prefix === null) {
            $this->_prefix = $this->_dependencyInjector->configure->appID . ':cache:';
        }

        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return apc_exists($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return apc_fetch($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @throws \ManaPHP\Cache\Engine\Apc\Exception
     */
    public function set($key, $value, $ttl)
    {
        $r = apc_store($this->_prefix . $key, $value, $ttl);
        if (!$r) {
            throw new ApcException('apc_store failed for `:key` key'/**m044d8697223644728*/, ['key' => $key]);
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        apc_delete($this->_prefix . $key);
    }
}