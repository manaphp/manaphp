<?php

namespace ManaPHP\Data\Db\Model\Metadata\Adapter;

use ManaPHP\Data\Db\Model\Metadata;
use ManaPHP\Exception\ExtensionNotInstalledException;

class Apcu extends Metadata
{
    /**
     * @var string
     */
    protected $prefix = 'models_metadata:';

    /**
     * @var int
     */
    protected $ttl = 86400;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('apcu')) {
            throw new ExtensionNotInstalledException('apcu');
        }

        if (isset($options['prefix'])) {
            $this->prefix .= $options['prefix'];
        }

        if (isset($options['ttl'])) {
            $this->ttl = $options['ttl'];
        }
    }

    /**
     * @param string $key
     *
     * @return array|false
     */
    public function read($key)
    {
        return apcu_fetch($this->prefix . $key);
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return void
     */
    public function write($key, $data)
    {
        apcu_store($this->prefix . $key, $data, $this->ttl);
    }
}