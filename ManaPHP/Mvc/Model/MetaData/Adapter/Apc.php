<?php
namespace ManaPHP\Mvc\Model\MetaData\Adapter;

use ManaPHP\Mvc\Model\MetaData;

class Apc extends MetaData
{

    /**
     * @var string
     */
    protected $_prefix = 'manaphp:modelsMetadata:';

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * Apc constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (is_string($options)) {
            $options['prefix'] = $options;
        }

        if (isset($options['prefix'])) {
            $this->_prefix .= $options['prefix'];
        }

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
        }
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