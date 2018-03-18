<?php

namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Configuration\Configure\Exception as ConfigureException;

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
    public $timezone = 'UTC';

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
     * @param string|array $files
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadFile($files)
    {
        foreach ((array)$files as $file) {
            /**
             * @var \ManaPHP\Configuration\Configure\EngineInterface $loader
             */
            $loader = $this->_dependencyInjector->getShared('ManaPHP\Configuration\Configure\Engine\\' . ucfirst(pathinfo($file, PATHINFO_EXTENSION)));
            $data = $loader->load($this->_dependencyInjector->alias->resolve($file));

            $this->loadData($data);
        }

        return $this;
    }

    /**
     * @param array $data
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadData($data)
    {
        $properties = get_object_vars($this);

        foreach ($data as $field => $value) {
            $f_value = null;
            if (strpos($field, ':') !== false) {
                list($field, $f_value) = explode(':', $field);
            }

            if (!isset($properties[$field])) {
                throw new ConfigureException(['`:item` item is not allowed: it must be a public property of `configure` component', 'item' => $field]);
            }

            if ($f_value) {
                if (preg_match('#^(.*)([+-=])$#', $f_value, $match) === 1) {
                    $f_env = $match[1];
                    /** @noinspection MultiAssignmentUsageInspection */
                    $f_op = $match[2];
                } else {
                    $f_env = $f_value;
                    $f_op = '=';
                }

                if ($f_env[0] === '!' ? !in_array($this->env, explode(',', substr($f_env, 1)), true) : in_array($this->env, explode(',', $f_env), true)) {
                    if ($f_op === '=') {
                        null;
                    } elseif ($f_op === '+') {
                        /** @noinspection SlowArrayOperationsInLoopInspection */
                        $value = array_merge($this->$field, $value);
                    } elseif ($f_op === '-') {
                        $value = isset($this->$field[0]) ? array_diff($this->$field, $value) : array_diff_key($this->$field, array_flip($value));
                    }
                }
            }

            $this->$field = $value;
        }

        return $this;
    }
}