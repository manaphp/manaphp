<?php

namespace ManaPHP\Event\Listener;

use ManaPHP\Helper\LocalFS;

class Provider extends \ManaPHP\Di\Provider
{
    public function boot($container)
    {
        $eventManager = $container->get('eventManager');
        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            $listener = lcfirst(basename($file, '.php'));
            $eventManager->addListener('App\Listeners\\' . ucfirst($listener));
        }
    }
}