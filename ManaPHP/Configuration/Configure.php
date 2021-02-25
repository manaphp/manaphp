<?php

namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\AliasInterface $alias
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
     * @param string $name
     * @param mixed  $definition
     *
     * @return \ManaPHP\Di\ContainerInterface
     */
    public function setShared($name, $definition)
    {
        return $this->container->setShared($name, $definition);
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
                $this->setShared($component, $definition);
            } else {
                $this->setShared($component, $definition);
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
    public function registerTracers()
    {
        foreach ($this->appGlob('Tracers/?*Tracer.php') as $file) {
            $command = basename($file, '.php');
            $this->setShared(lcfirst($command), "App\Tracers\\$command");
        }

        if (in_array('*', $this->tracers, true)) {
            foreach ($this->getDefinitions('*Tracer') as $name => $_) {
                $this->getShared($name);
            }
        } else {
            foreach ($this->tracers as $tracer) {
                $this->getShared(lcfirst($tracer) . 'Tracer');
            }
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerCommands()
    {
        foreach ($this->appGlob('Commands/?*Command.php') as $file) {
            $command = basename($file, '.php');
            $this->setShared(lcfirst($command), "App\Commands\\$command");
        }

        return $this;
    }

    /**
     * @return static
     */
    public function registerAspects()
    {
        foreach ($this->appGlob('Aspects/?*Aspect.php') as $item) {
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
        foreach ($this->appGlob('Services/?*Service.php') as $file) {
            $service = lcfirst(basename($file, '.php'));

            if (($params = $this->services[$service] ?? null) === null) {
                $this->setShared($service, 'App\Services\\' . ucfirst($service));
            } else {
                if (!is_array($params)) {
                    $params = [$params];
                }
                $params['class'] = 'App\Services\\' . ucfirst($service);
                $this->setShared($service, $params);
            }
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
                $this->setShared($pluginName, $definition);
            } else {
                if (is_string($k)) {
                    $this->setShared($pluginName, $v);
                }
            }
            $this->getShared($pluginName);
        }

        foreach ($app_plugins as $plugin => $_) {
            $pluginClassName = "App\\Plugins\\$plugin";
            $plugin = lcfirst($plugin);
            $this->setShared($plugin, $pluginClassName)->getShared($plugin);
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