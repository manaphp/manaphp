<?php
namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Configuration\Settings\Exception as SettingsException;

class Settings extends Component implements \ArrayAccess, SettingsInterface
{
    /**
     * @var string|\ManaPHP\Configuration\Settings\EngineInterface
     */
    protected $_engine;

    /**
     * @var array
     */
    protected $_settings;

    /**
     * @var int
     */
    protected $_ttl = 0;

    /**
     * @var float
     */
    protected $_updated_time = 0.0;

    /**
     * Settings constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = 'ManaPHP\Configuration\Settings\Engine\Redis')
    {
        if (is_string($options) || is_object($options)) {
            $options = ['engine' => $options];
        }
        $this->_engine = $options['engine'];

        if (isset($options['ttl'])) {
            $this->_ttl = (int)$options['ttl'];
        }
    }

    /**
     * @return \ManaPHP\Configuration\Settings\EngineInterface
     */
    protected function _getEngine()
    {
        if (is_string($this->_engine)) {
            return $this->_engine = $this->_dependencyInjector->getShared($this->_engine);
        } else {
            return $this->_engine = $this->_dependencyInjector->getInstance($this->_engine);
        }
    }

    /**
     * @param string $section
     * @param string $key
     * @param string $defaultValue
     *
     * @return string|array
     */
    public function get($section, $key = null, $defaultValue = '')
    {
        if ($this->_ttl && microtime(true) - $this->_updated_time > $this->_ttl) {
            $this->_updated_time = microtime(true);

            unset($this->_settings[$section]);
        }

        if (!isset($this->_settings[$section])) {
            $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
            $this->_settings[$section] = $engine->get($section);
        }

        $values = $this->_settings[$section];
        if ($key === null) {
            return $values;
        } else {
            return isset($values[$key]) ? $values[$key] : $defaultValue;
        }
    }

    /**
     * @param string       $section
     * @param string|array $key
     * @param string       $value
     *
     * @return void
     */
    public function set($section, $key, $value = null)
    {
        if ($this->_ttl && time() - $this->_updated_time > $this->_ttl) {
            $this->_updated_time = microtime(true);

            unset($this->_settings[$section]);
        }

        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();

        if (!isset($this->_settings[$section])) {
            $this->_settings[$section] = $engine->get($section);
        }

        if (is_array($key)) {
            $this->_settings[$section] = array_merge($this->_settings[$section], $key);
            $engine->set($section, $this->_settings[$section]);
        } else {
            $this->_settings[$section][$key] = $value;
            $engine->set($section, $key, $value);
        }
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return bool
     */
    public function exists($section, $key = null)
    {
        if ($this->_ttl && time() - $this->_updated_time > $this->_ttl) {
            $this->_updated_time = microtime(true);

            unset($this->_settings[$section]);
        }

        if (!isset($this->_settings[$section])) {
            $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
            $this->_settings[$section] = $engine->get($section);
        }

        if ($key === null) {
            return count($this->_settings[$section]) !== 0;
        } else {
            return isset($this->_settings[$section][$key]);
        }
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return void
     */
    public function delete($section, $key)
    {
        if ($this->_ttl && time() - $this->_updated_time > $this->_ttl) {
            $this->_updated_time = microtime(true);

            unset($this->_settings[$section]);
        }

        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        if ($key === null) {
            $this->_settings[$section] = [];
        } else {
            unset($this->_settings[$section][$key]);
        }

        $engine->delete($section, $key);
    }

    /**
     * @param mixed $offset
     *
     * @return string|array
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new SettingsException('not support offsetUnset method');
    }

    public function offsetUnset($offset)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new SettingsException('not support offsetUnset method');
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }
}