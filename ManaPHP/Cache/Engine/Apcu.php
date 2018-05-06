<?php

namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\Engine\Apcu\Exception as ApcuException;
use ManaPHP\Cache\EngineInterface;
use ManaPHP\Component;

/**
 * Class ManaPHP\Cache\Adapter\Apcu
 *
 * @package cache\engine
 */
class Apcu extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * Apcu constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['prefix' => $options];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return apcu_exists($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return apcu_fetch($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @throws \ManaPHP\Cache\Engine\Apcu\Exception
     */
    public function set($key, $value, $ttl)
    {
        $r = apcu_store($this->_prefix . $key, $value, $ttl);
        if (!$r) {
            throw new ApcuException(['apcu_store failed for `:key` key', 'key' => $key]);
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        apcu_delete($this->_prefix . $key);
    }
}