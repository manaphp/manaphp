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
            /** @var \ManaPHP\Event\ListenInterface $listener */
            $listener = $this->container->get('App\Listener\\' . basename($file, '.php'));
            $listener->listen();
        }
    }
}