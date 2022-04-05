<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use Psr\Container\ContainerInterface;

class ListenerBootstrapper extends Component implements BootstrapperInterface
{
    public function bootstrap(ContainerInterface $container): void
    {
        /** @var \ManaPHP\Event\ListenInterface $listener */

        foreach (LocalFS::glob('@app/Listeners/?*Listener.php') as $file) {
            $listener = $container->get('App\Listeners\\' . basename($file, '.php'));
            $listener->listen();
        }

        foreach (LocalFS::glob('@app/Areas/*', GLOB_ONLYDIR) as $item) {
            $area = basename($item);
            foreach (LocalFS::glob("$item/Listeners/?*Listener.php") as $file) {
                $listener = $container->get("App\\Areas\\$area\\Listeners\\" . basename($file, '.php'));
                $listener->listen();
            }
        }
    }
}