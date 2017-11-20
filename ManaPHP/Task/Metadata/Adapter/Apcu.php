<?php

namespace ManaPHP\Task\Metadata\Adapter;

use ManaPHP\Component;
use ManaPHP\Task\Metadata\Adapter\Apcu\Exception as ApcuException;
use ManaPHP\Task\Metadata\AdapterInterface;

/**
 * Class ManaPHP\Task\Metadata\Adapter\Apcu
 *
 * @package tasksMetadata\adapter
 */
class Apcu extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'tasks_metadata:';

    /**
     * Apcu constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Task\Metadata\Adapter\Exception
     */
    public function __construct($options = 'tasks_metadata:')
    {
        if (!extension_loaded('apcu')) {
            throw new ApcuException('`apcu` is not installed, or the extension is not loaded'/**m06424012cd041dd33*/);
        }
        if (is_string($options)) {
            $this->_prefix = $options;
        } else {
            if (isset($options['prefix'])) {
                $this->_prefix = $options['prefix'];
            }
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        return apcu_fetch($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        apcu_store($key, $value);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        apcu_delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return apcu_exists($key);
    }
}