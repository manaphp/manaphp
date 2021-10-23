<?php

namespace ManaPHP\Configurators;

use ManaPHP\Component;
use ManaPHP\ConfiguratorInterface;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\Event\ManagerInterface $eventManager
 */
class ListenerConfigurator extends Component implements ConfiguratorInterface
{
    public function configure()
    {
        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            $listener = 'App\Listener\\' . (basename($file, '.php'));
            $this->container->get($listener);
        }
    }
}