<?php

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class ListenerBootstrapper extends Component implements BootstrapperInterface
{
    public function bootstrap()
    {
        /** @var \ManaPHP\Event\ListenInterface $listener */

        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            $listener = $this->container->get('App\Listeners\\' . basename($file, '.php'));
            $listener->listen();
        }

        foreach (LocalFS::glob('@app/Areas/*', GLOB_ONLYDIR) as $item) {
            $area = basename($item);
            foreach (LocalFS::glob("$item/Listeners/?*Listener.php") as $file) {
                $listener = $this->container->get("App\\Areas\\$area\\Listeners\\" . basename($file, '.php'));
                $listener->listen();
            }
        }
    }
}