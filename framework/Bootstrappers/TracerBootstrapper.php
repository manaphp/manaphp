<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use Psr\Container\ContainerInterface;

class TracerBootstrapper extends Component implements BootstrapperInterface
{
    #[Inject] protected ConfigInterface $config;

    #[Value] protected array $tracers = ['*'];
    #[Value] protected ?bool $enabled = null;

    public function bootstrap(ContainerInterface $container): void
    {
        if (!($this->enabled && in_array($this->config->get('env'), ['dev', 'test'], true))) {
            return;
        }

        /** @var \ManaPHP\Tracer $tracer */

        if (in_array('*', $this->tracers, true)) {
            foreach (LocalFS::glob('@manaphp/Tracers/?*Tracer.php') as $file) {
                $name = basename($file, 'Tracer.php');
                $tracer = $container->get("ManaPHP\Tracers\\{$name}Tracer");
                $tracer->listen();
            }
        } else {
            foreach ($this->tracers as $name) {
                $name = Str::camelize($name);
                $tracer = $container->get("ManaPHP\Tracers\\{$name}Tracer");
                $tracer->listen();
            }
        }

        foreach (LocalFS::glob('@app/Tracers/?*Tracer.php') as $file) {
            $tracer = $container->get('App\Tracers\\' . basename($file, '.php'));
            $tracer->listen();
        }

        foreach (LocalFS::glob('@app/Areas/*', GLOB_ONLYDIR) as $item) {
            $area = basename($item);
            foreach (LocalFS::glob("$item/Tracers/?*Tracer.php") as $file) {
                $tracer = $container->get("App\\Areas\\$area\\Tracers\\" . basename($file, '.php'));
                $tracer->listen();
            }
        }
    }
}