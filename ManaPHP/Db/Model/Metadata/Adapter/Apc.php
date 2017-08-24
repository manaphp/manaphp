<?php

namespace ManaPHP\Db\Model\Metadata\Adapter;

use ManaPHP\Db\Model\Metadata;
use ManaPHP\Db\Model\Metadata\Adapter\Apc\Exception as ApcException;

/**
 * Class ManaPHP\Mvc\Model\Metadata\Adapter\Apc
 *
 * @package modelsMetadata\adapter
 */
class Apc extends Metadata
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * Apc constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Db\Model\Metadata\Adapter\Exception
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('apc')) {
            throw new ApcException('`apc` is not installed, or the extension is not loaded'/**m0763710a465cf1bb2*/);
        }

        if (is_object($options)) {
            $options = (array)$options;
        }

        if (is_string($options)) {
            $options = ['prefix' => $options];
        }

        if (isset($options['prefix'])) {
            $this->_prefix .= $options['prefix'];
        }

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
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
            $this->_prefix = $this->_dependencyInjector->configure->appID . ':models_metadata:';
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
     * @return array|false
     */
    public function read($key)
    {
        return apc_fetch($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return void
     */
    public function write($key, $data)
    {
        apc_store($this->_prefix . $key, $data, $this->_ttl);
    }
}