<?php
namespace ManaPHP\Configuration\Settings\Adapter;

use ManaPHP\Component;
use ManaPHP\Configuration\SettingsInterface;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;

class Redis extends Component implements SettingsInterface
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * Settings constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_prefix = $options['prefix'] ?? 'settings:';
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function get($key)
    {
        $value = json_parse($this->redisDb->get($this->_prefix . $key) ?: '[]');
        if (!is_array($value)) {
            throw new InvalidJsonException('the settings of `:key` key value is not json format', ['key' => $key]);
        }
        return $value;
    }

    /**
     * @param string|array $key
     * @param array        $value
     *
     * @return static
     */
    public function set($key, $value)
    {
        if (!is_array($value)) {
            throw new InvalidValueException(['the settings of `:key` key value must be array', 'key' => $key]);
        }

        $this->redisDb->set($this->_prefix . $key, json_stringify($value));

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->redisDb->exists($this->_prefix . $key);
    }

    /**
     * @param string $key
     *
     * @return static
     */
    public function delete($key)
    {
        $this->redisDb->del($this->_prefix . $key);

        return $this;
    }
}
