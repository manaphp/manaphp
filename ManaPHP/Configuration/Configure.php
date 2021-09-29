<?php

namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\Di\ContainerInterface $container
 * @property-read \ManaPHP\AliasInterface        $alias
 */
class Configure extends Component implements ConfigureInterface
{
    /**
     * @var array
     */
    protected $config;

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
     * @var array
     */
    public $tracers = [];

    /**
     * @param string $file
     *
     * @return static
     */
    public function load($file = '@config/app.php')
    {
        /** @noinspection PhpIncludeInspection */
        $this->config = require $this->alias->resolve($file);

        foreach ((array)$this->config as $field => $value) {
            if (!property_exists($this, $field)) {
                throw new NotSupportedException(['`%s` must be a public property of `configure` component', $field]);
            }

            $this->$field = $value;
        }

        if (defined('APP_ID')) {
            $this->id = APP_ID;
        } else {
            define('APP_ID', $this->id);
        }

        if (defined('APP_DEBUG')) {
            $this->debug = APP_DEBUG;
        } else {
            define('APP_DEBUG', $this->debug);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
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
                throw new InvalidValueException(['`%s` param is not exists in $configure->params', $name]);
            } else {
                return $default;
            }
        } else {
            return $value;
        }
    }

    /**
     * @param string $pattern
     *
     * @return array
     */
    public function getDefinitions($pattern = null)
    {
        return $this->container->getDefinitions($pattern);
    }

    /**
     * @return static
     */
    public function registerAliases()
    {
        foreach ($this->aliases as $alias => $path) {
            $this->alias->set($alias, $path);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerComponents()
    {
        foreach ($this->components as $component => $definition) {
            if (is_int($component)) {
                $component = lcfirst(($pos = strrpos($definition, '\\')) ? substr($definition, $pos + 1) : $definition);
                $this->container->set($component, $definition);
            } else {
                $this->container->set($component, $definition);
            }
        }

        return $this;
    }

    /**
     * @param string $glob
     *
     * @return array
     */
    public function appGlob($glob)
    {
        if ($appDir = $this->alias->get('@app')) {
            return glob("$appDir/$glob") ?? [];
        } else {
            return [];
        }
    }

    /**
     * @return static
     */
    public function registerAspects()
    {
        foreach ($this->appGlob('Aspects/?*Aspect.php') as $item) {
            $class = 'App\Aspects\\' . basename($item, '.php');
            $this->container->get($class);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerPlugins()
    {
        $app_plugins = [];
        foreach ($this->appGlob('Plugins/?*Plugin.php') as $item) {
            $app_plugins[basename($item, '.php')] = 1;
        }

        foreach ($this->plugins as $k => $v) {
            $plugin = is_string($k) ? $k : $v;
            if (($pos = strrpos($plugin, 'Plugin')) === false || $pos !== strlen($plugin) - 6) {
                $plugin .= 'Plugin';
            }

            if ($plugin[0] === '!') {
                unset($app_plugins[ucfirst(substr($plugin, 1))]);
                continue;
            }

            $plugin = ucfirst($plugin);
            $pluginName = lcfirst($plugin);

            if (isset($app_plugins[$plugin])) {
                unset($app_plugins[$plugin]);
                $pluginClassName = "App\\Plugins\\$plugin";
                $definition = is_int($k) ? $pluginClassName : array_merge($v, ['class' => $pluginClassName]);
                $this->container->set($pluginName, $definition);
            } else {
                if (is_string($k)) {
                    $this->container->set($pluginName, $v);
                }
            }
            $this->container->get($pluginName);
        }

        foreach ($app_plugins as $plugin => $_) {
            $pluginClassName = "App\\Plugins\\$plugin";
            $plugin = lcfirst($plugin);
            $this->container->set($plugin, $pluginClassName)->get($plugin);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerListeners()
    {
        foreach ($this->listeners as $listener) {
            if ($listener === '*') {
                foreach ($this->appGlob('Areas/*/Listeners/?*Listener.php') as $item) {
                    $item = str_replace($this->alias->get('@app'), 'App', $item);
                    $item = substr(str_replace('/', '\\', $item), 0, -4);
                    $this->eventManager->addListener($item);
                }

                foreach ($this->appGlob('Listeners/?*Listener.php') as $item) {
                    $item = str_replace($this->alias->get('@app'), 'App', $item);
                    $item = substr(str_replace('/', '\\', $item), 0, -4);
                    $this->eventManager->addListener($item);
                }
            } else {
                $this->eventManager->addListener($listener);
            }
        }

        return $this;
    }

    public function dump()
    {
        $data = parent::dump();
        unset($data['config']);

        return $data;
    }
}