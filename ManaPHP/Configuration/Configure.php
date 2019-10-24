<?php

namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;

/**
 * Class ManaPHP\Configuration\Configure
 *
 * @package configure
 *
 */
class Configure extends Component implements ConfigureInterface
{
    /**
     * @var string
     */
    public $id = 'app';

    /**
     * @var string
     */
    public $name = 'ManaPHP';

    /**
     * @var string
     */
    public $env = 'dev';

    /**
     * @var bool
     */
    public $debug = true;

    /**
     * @var string
     */
    public $version = '1.0.0';

    /**
     * @var string
     */
    public $timezone = '';

    /**
     * @var string
     */
    public $language = 'en';

    /**
     * @var string
     */
    public $master_key = '';

    /**
     * @var array
     */
    public $services = [];

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var array
     */
    public $aliases = [];

    /**
     * @var array
     */
    public $components = [];

    /**
     * @var array
     */
    public $listeners = ['*'];

    /**
     * @var array
     */
    public $plugins = [];

    /**
     * @param string $file
     *
     * @return static
     */
    public function load($file = '@config/app.php')
    {
        /** @noinspection PhpIncludeInspection */
        $data = require $this->alias->resolve($file);

        foreach ((array)$data as $field => $value) {
            if (!property_exists($this, $field)) {
                throw new NotSupportedException(['`:item` item is not allowed: it must be a public property of `configure` component', 'item' => $field]);
            }

            $this->$field = $value;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        $value = Arr::get($this->params, $name);
        if ($value === null) {
            if ($default === null) {
                throw new InvalidValueException(['`:param` param is not exists in $configure->params', 'param' => $name]);
            } else {
                return $default;
            }
        } else {
            return $value;
        }
    }
}