<?php

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class ListenerBootstrapper extends Component implements BootstrapperInterface
{
    public function bootstrap()
    {
        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            /** @var \ManaPHP\Event\ListenInterface $listener */
            $listener = $this->container->get('App\Listeners\\' . basename($file, '.php'));
            $listener->listen();
        }
    }
}