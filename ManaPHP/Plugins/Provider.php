<?php

namespace ManaPHP\Plugins;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Helper\Str;

class Provider extends \ManaPHP\Di\Provider
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     */
    public function boot($container)
    {
        $plugins = $container->get('config')->get('plugins');
        $pluginManager = $container->get('pluginManager');
        foreach ($plugins as $plugin) {
            if ($plugin[0] === '!') {
                continue;
            }

            $camelizedPlugin = Str::camelize($plugin);
            if (($definition = $pluginManager->getPlugins()[$camelizedPlugin] ?? null) === null) {
                throw new InvalidValueException("$camelizedPlugin is not exists");
            } else {
                $container->get($definition);
            }
        }
    }
}