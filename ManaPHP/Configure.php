<?php

namespace ManaPHP;

use ManaPHP\Configure\Exception as ConfigureException;

/**
 * Class ManaPHP\Configure
 *
 * @package configure
 *
 */
class Configure extends Component implements ConfigureInterface
{
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
    public $timezone = 'UTC';

    /**
     * @var string
     */
    public $appID = 'manaphp';

    /**
     * @var string
     */
    public $master_key;

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
    public $modules = ['Home' => '/'];

    /**
     * @var array
     */
    public $params = [];

    /**
     * @param string $file
     * @param string $env
     *
     * @return static
     * @throws \ManaPHP\Configure\Exception
     */
    public function load($file, $env = null)
    {
        /**
         * @var \ManaPHP\Configure\EngineInterface $loader
         */
        $loader = $this->_dependencyInjector->getShared('ManaPHP\Configure\Engine\\' . ucfirst(pathinfo($file, PATHINFO_EXTENSION)));
        $data = $loader->load($this->_dependencyInjector->alias->resolve($file));

        if ($env !== null) {
            foreach ($data as $k => $v) {
                if (strpos($k, ':') !== false) {
                    list($kName, $kEnv) = explode(':', $k);

                    if ($kEnv === $env) {
                        if (isset($data[$kName])) {
                            /** @noinspection SlowArrayOperationsInLoopInspection */
                            $data[$kName] = array_merge($data[$kName], $v);
                        } else {
                            $data[$kName] = $v;
                        }
                    }

                    unset($data[$k]);
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