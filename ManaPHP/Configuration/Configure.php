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
    protected $_file = '@config/app.php';

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
    public $timezone;

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
     * @return static
     */
    public function load()
    {
        /** @noinspection PhpIncludeInspection */
        $this->loadData(require $this->_di->alias->resolve($this->_file));

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
            $f_value = null;
            if (strpos($field, ':') !== false) {
                list($field, $f_value) = explode(':', $field);
            }

            if (!isset($properties[$field])) {
                throw new NotSupportedException(['`:item` item is not allowed: it must be a public property of `configure` component', 'item' => $field]);
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

        if (is_string($this->traces)) {
            $this->traces = preg_split('#[\s,]+#', $this->traces, -1, PREG_SPLIT_NO_EMPTY);
        }

        return $this;
    }
}