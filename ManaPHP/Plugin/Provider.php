<?php

namespace ManaPHP\Plugin;

use ManaPHP\Helper\LocalFS;

class Provider extends \ManaPHP\Di\Provider
{
    public function __construct()
    {
        foreach (LocalFS::glob('@app/Plugins/?*Plugin.php') as $file) {
            $plugin = lcfirst(basename($file, '.php'));
            $this->definitions[$plugin] = 'App\Plugins\\' . ucfirst($plugin);
        }
    }

    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     */
    public function boot($container)
    {
        $plugins = $container->get('configure')->plugins;

        foreach ($plugins as $plugin) {
            if ($plugin[0] === '!') {
                continue;
            }

            $container->get($plugin . 'Plugin');
        }
    }
}