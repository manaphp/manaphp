<?php

namespace ManaPHP\Providers;

use ManaPHP\Helper\LocalFS;
use ManaPHP\Provider;

class ListenerProvider extends Provider
{
    public function boot()
    {
        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            /** @var \ManaPHP\Event\ListenInterface $listener */
            $listener = $this->container->get('App\Listeners\\' . basename($file, '.php'));
            $listener->listen();
        }
    }
}