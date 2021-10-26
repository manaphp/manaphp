<?php

namespace ManaPHP\Event\Listener;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class Manager extends Component implements ManagerInterface
{
    public function listen()
    {
        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            /** @var \ManaPHP\Event\ListenInterface $listener */
            $listener = $this->container->get('App\Listeners\\' . basename($file, '.php'));
            $listener->listen();
        }
    }
}