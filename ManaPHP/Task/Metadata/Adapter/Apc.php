<?php
namespace ManaPHP\Task\Metadata\Adapter;

use ManaPHP\Task\Metadata;

class Apc extends Metadata
{
    /**
     * Apc constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Task\Metadata\Adapter\Exception
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('apc')) {
            throw new Exception('`apc` is not installed, or the extension is not loaded.');
        }

        $this->_prefix = 'manaphp:task:';

        parent::__construct($options);
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function _get($key)
    {
        return apc_fetch($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function _set($key, $value)
    {
        apcu_store($key, $value);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function _delete($key)
    {
        apc_delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function _exists($key)
    {
        apc_exists($key);
    }
}