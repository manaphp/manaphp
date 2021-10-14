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
            foreach (LocalFS::glob('@manaphp/Plugins/?*Plugin.php') as $file) {
                $name = basename($file, 'Plugin.php');
                $this->plugins[lcfirst($name)] = "ManaPHP\Plugins\\{$name}Plugin";
            }

            foreach (LocalFS::glob('@app/Plugins/?*Plugin.php') as $file) {
                $name = basename($file, 'Plugin.php');
                $this->plugins[lcfirst($name)] = "App\Plugins\\{$name}Plugin";
            }
        }

        return $this->plugins;
    }
}