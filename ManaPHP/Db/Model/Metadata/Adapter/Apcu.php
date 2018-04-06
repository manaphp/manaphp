<?php

namespace ManaPHP\Db\Model\Metadata\Adapter;

use ManaPHP\Db\Model\Metadata;
use ManaPHP\Exception\NotSupportedException;

/**
 * Class ManaPHP\Mvc\Model\Metadata\Adapter\Apcu
 *
 * @package modelsMetadata\adapter
 */
class Apcu extends Metadata
{
    /**
     * @var string
     */
    protected $_prefix = 'models_metadata:';

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * Apcu constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = 'models_metadata:')
    {
        if (!extension_loaded('apcu')) {
            throw new NotSupportedException('`apcu` is not installed, or the extension is not loaded'/**m0763710a465cf1bb2*/);
        }

        if (is_string($options)) {
            $this->_prefix = $options;
        } else {
            if (isset($options['prefix'])) {
                $this->_prefix .= $options['prefix'];
            }

            if (isset($options['ttl'])) {
                $this->_ttl = $options['ttl'];
            }
        }
    }

    /**
     * @param string $key
     *
     * @return array|false
     */
    public function read($key)
    {
        return apcu_fetch($this->_prefix . $key);
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return void
     */
    public function write($key, $data)
    {
        apcu_store($this->_prefix . $key, $data, $this->_ttl);
    }
}