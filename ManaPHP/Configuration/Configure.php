<?php

namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;

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
    public $bootstraps = [];

    /**
     * @var array
     */
    public $listeners = ['*'];

    /**
     * @var array
     */
    public $plugins = ['*'];

    /**
     * @param string $file
     *
     * @return static
     */
    public function load($file = '@config/app.php')
    {
        /** @noinspection PhpIncludeInspection */
        $data = require $this->alias->resolve($file);

        $properties = get_object_vars($this);
        foreach ((array)$data as $field => $value) {
            if (!isset($properties[$field]) && !array_key_exists($field, $properties)) {
                throw new NotSupportedException(['`:item` item is not allowed: it must be a public property of `configure` component', 'item' => $field]);
            }

            $this->$field = $value;
        }

        return $this;
    }
}