<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Debugging\DebuggerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\ExporterInterface;
use ManaPHP\Http\Router\MappingScannerInterface;
use ManaPHP\Swoole\ProcessesInterface;
use ManaPHP\Swoole\WorkersInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractServer implements ServerInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected HandlerInterface $httpHandler;

    #[Autowired] protected string $host = '0.0.0.0';
    #[Autowired] protected int $port = 9501;

    #[Autowired] protected array $bootstrappers
        = [
            DebuggerInterface::class,
            WorkersInterface::class,
            ExporterInterface::class,
            ProcessesInterface::class,
            MappingScannerInterface::class,
        ];

    protected function bootstrap(): void
    {
        foreach ($this->bootstrappers as $name) {
            if ($name !== '' && $name !== null) {
                /** @var BootstrapperInterface $bootstrapper */
                $bootstrapper = $this->container->get($name);
                $bootstrapper->bootstrap();
            }
        }
    }
}