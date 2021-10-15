<?php

namespace ManaPHP\Plugin;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var array
     */
    protected $plugins = [];

    /**
     * @return array
     */
    public function getPlugins()
    {
        if ($this->plugins === []) {

            $plugins = [];

            foreach (LocalFS::glob('@manaphp/Plugins/?*Plugin.php') as $file) {
                $name = basename($file, 'Plugin.php');
                $plugins[lcfirst($name)] = "ManaPHP\Plugins\\{$name}Plugin";
            }

            foreach (LocalFS::glob('@app/Plugins/?*Plugin.php') as $file) {
                $name = basename($file, 'Plugin.php');
                $plugins[lcfirst($name)] = "App\Plugins\\{$name}Plugin";
            }

            ksort($plugins);

            $this->plugins = $plugins;
        }

        return $this->plugins;
    }
}