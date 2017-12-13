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
     * @param string $file
     * @param string $env
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadFile($file, $env = null)
    {
        /**
         * @var \ManaPHP\Configuration\Configure\EngineInterface $loader
         */
        $loader = $this->_dependencyInjector->getShared('ManaPHP\Configuration\Configure\Engine\\' . ucfirst(pathinfo($file, PATHINFO_EXTENSION)));
        $data = $loader->load($this->_dependencyInjector->alias->resolve($file));

        return $this->loadData($data, $env);
    }

    /**
     * @param array  $data
     * @param string $env
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadData($data, $env = null)
    {
        if ($env !== null) {
            foreach ($data as $field => $value) {
                if (strpos($field, ':') !== false) {
                    list($f_name, $f_value) = explode(':', $field);
                    if (preg_match('#^(.*)([+-=])$#', $f_value, $match) === 1) {
                        $f_env = $match[1];
                        /** @noinspection MultiAssignmentUsageInspection */
                        $op = $match[2];
                    } else {
                        $f_env = $f_value;
                        $op = '=';
                    }

                    if ($f_env[0] === '!' ? !in_array($env, explode(',', substr($f_env, 1)), true) : in_array($env, explode(',', $f_env), true)) {
                        if ($op === '=') {
                            $data[$f_name] = $value;
                        } elseif ($op === '+') {
                            $data[$f_name] = array_merge(isset($data[$f_name]) ? $data[$f_name] : [], $value);
                        } elseif ($op === '-') {
                            $data[$f_name] = isset($data[$f_name][0]) ? array_diff($data[$f_name], $value) : array_diff_key($data[$f_name], array_flip($value));
                        }
                    }

                    unset($data[$field]);
                }
            }
        }

        $properties = array_keys(get_object_vars($this));

        foreach ($data as $name => $value) {
            if ($name[0] === '_' || !in_array($name, $properties, true)) {
                throw new ConfigureException('`:item` item is not allowed: it must be a public property of `configure` component', ['item' => $name]);
            }

            $this->$name = $value;
        }

        return $this;
    }
}