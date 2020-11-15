<?php

namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\LocalFS;

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
                throw new NotSupportedException(['`%s` must be a public property of `configure` component', $field]);
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
                throw new InvalidValueException(['`%s` param is not exists in $configure->params', $name]);
            } else {
                return $default;
            }
        } else {
            return $value;
        }
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
        $di = $this->_di;

        foreach ($this->components as $component => $definition) {
            if (is_int($component)) {
                $component = lcfirst(($pos = strrpos($definition, '\\')) ? substr($definition, $pos + 1) : $definition);
                $di->setShared($component, $definition);
            } elseif ($definition === null) {
                $di->remove($component);
            } elseif ($component[0] !== '!' || $di->has($component = substr($component, 1))) {
                $di->setShared($component, $definition);
            }
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerAspects()
    {
        foreach (LocalFS::glob('@app/Aspects/*Aspect.php') as $item) {
            $class = 'App\Aspects\\' . basename($item, '.php');
            /** @var \ManaPHP\Aop\Aspect $aspect */
            $aspect = new $class();
            $aspect->register();
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerServices()
    {
        $di = $this->_di;

        foreach (@scandir($this->alias->resolve('@app/Services')) ?: [] as $file) {
            if (substr($file, -11) === 'Service.php') {
                $service = lcfirst(basename($file, '.php'));
                if (!isset($services[$service])) {
                    $services[$service] = [];
                }
            }
        }

        foreach ($this->services as $service => $params) {
            if (is_string($params)) {
                $params = [$params];
            }
            $params['class'] = 'App\Services\\' . ucfirst($service);
            $di->setShared($service, $params);
        }

        return $this;
    }


    /**
     * @return static
     */
    public function registerPlugins()
    {
        $di = $this->_di;

        $app_plugins = [];
        foreach (LocalFS::glob('@app/Plugins/*Plugin.php') as $item) {
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
                $di->setShared($pluginName, $definition);
            } else {
                if (is_string($k)) {
                    $di->setShared($pluginName, $v);
                }
            }
            $di->getShared($pluginName);
        }

        foreach ($app_plugins as $plugin => $_) {
            $pluginClassName = "App\\Plugins\\$plugin";
            $plugin = lcfirst($plugin);
            $di->setShared($plugin, $pluginClassName)->getShared($plugin);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerListeners()
    {
        $eventsManager = $this->getShared('eventsManager');

        foreach ($this->listeners as $listener) {
            if ($listener === '*') {
                foreach (LocalFS::glob('@app/Areas/*/Listeners/*Listener.php') as $item) {
                    $item = str_replace($this->alias->get('@app'), 'App', $item);
                    $item = substr(str_replace('/', '\\', $item), 0, -4);
                    $eventsManager->addListener($item);
                }

                foreach (LocalFS::glob('@app/Listeners/*Listener.php') as $item) {
                    $item = str_replace($this->alias->get('@app'), 'App', $item);
                    $item = substr(str_replace('/', '\\', $item), 0, -4);
                    $eventsManager->addListener($item);
                }
            } else {
                $eventsManager->addListener($listener);
            }
        }

        return $this;
    }
}