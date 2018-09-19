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
    public $env = 'prod';

    /**
     * @var bool
     */
    public $debug = false;

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
    public $traces = [];

    /**
     * @param string $file
     *
     * @return static
     */
    public function load($file = '@config/app.php')
    {
        /** @noinspection PhpIncludeInspection */
        $this->loadData(require $this->alias->resolve($file));

        return $this;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public function loadData($data)
    {
        $properties = get_object_vars($this);

        foreach ($data as $field => $value) {
            if (!isset($properties[$field])) {
                throw new NotSupportedException(['`:item` item is not allowed: it must be a public property of `configure` component', 'item' => $field]);
            }

            $this->$field = $value;
        }

        if (is_string($this->traces)) {
            $this->traces = preg_split('#[\s,]+#', $this->traces, -1, PREG_SPLIT_NO_EMPTY);
        }

        return $this;
    }
}