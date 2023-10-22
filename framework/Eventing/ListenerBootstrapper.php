<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use Psr\Container\ContainerInterface;

class ListenerBootstrapper implements BootstrapperInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

    public function bootstrap(ContainerInterface $container): void
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