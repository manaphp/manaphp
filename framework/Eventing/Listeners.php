<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;

class Listeners implements ListenersInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

    public function bootstrap(): void
    {
        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            $this->listenerProvider->add('App\Listeners\\' . basename($file, '.php'));
        }

        foreach (LocalFS::glob('@app/Areas/*', GLOB_ONLYDIR) as $item) {
            $area = basename($item);
            foreach (LocalFS::glob("$item/Listeners/?*Listener.php") as $file) {
                $this->listenerProvider->add("App\\Areas\\$area\\Listeners\\" . basename($file, '.php'));
            }
        }
    }
}