<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidKeyException;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Config extends Component implements ConfigInterface
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    public function load($file = '@config/app.php')
    {
        return $this->config = require $this->alias->resolve($file);
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        $value = $this->config[$key] ?? $default;

        if ($value === null) {
            throw new InvalidKeyException('key');
        } else {
            return $value;
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        $old = $this->config[$key] ?? null;

        $this->config[$key] = $value;

        return $old;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->config[$key]);
    }
}