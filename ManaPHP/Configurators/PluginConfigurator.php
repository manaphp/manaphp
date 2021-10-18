<?php

namespace ManaPHP\Configurators;

use ManaPHP\Component;
use ManaPHP\ConfiguratorInterface;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\ConfigInterface         $config
 * @property-read \ManaPHP\Plugin\ManagerInterface $pluginManager
 */
class PluginConfigurator extends Component implements ConfiguratorInterface
{
    public function configure()
    {
        $plugins = $this->config->get('plugins');
        foreach ($plugins as $plugin) {
            if ($plugin[0] === '!') {
                continue;
            }

            $camelizedPlugin = Str::camelize($plugin);
            if (($definition = $this->pluginManager->getPlugins()[$camelizedPlugin] ?? null) === null) {
                throw new InvalidValueException("$camelizedPlugin is not exists");
            } else {
                $this->container->get($definition);
            }
        }
    }
}