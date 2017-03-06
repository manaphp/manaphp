<?php
namespace ManaPHP\Task\Metadata\Adapter;

use ManaPHP\Component;
use ManaPHP\Task\Metadata\Adapter\Apc\Exception as ApcException;
use ManaPHP\Task\Metadata\AdapterInterface;

/**
 * Class ManaPHP\Task\Metadata\Adapter\Apc
 *
 * @package tasksMetadata\adapter
 */
class Apc extends Component implements AdapterInterface
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
     * @throws \ManaPHP\Task\Metadata\Adapter\Exception
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('apc')) {
            throw new ApcException('`apc` is not installed, or the extension is not loaded'/**m06424012cd041dd33*/);
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
            $this->_prefix = $this->_dependencyInjector->configure->appID . ':tasks_metadata:';
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
     * @return mixed|false
     */
    public function get($key)
    {
        return apc_fetch($key);
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
        apc_delete($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return apc_exists($key);
    }
}