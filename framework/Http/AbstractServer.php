<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Di\Attribute\Autowired;
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
    #[Autowired] protected GlobalsInterface $globals;

    #[Autowired] protected string $host = '0.0.0.0';
    #[Autowired] protected int $port = 9501;

    #[Autowired] protected array $bootstrappers = [];

    protected function bootstrap(): void
    {
        foreach ($this->bootstrappers as $name) {
            /** @var BootstrapperInterface $bootstrapper */
            $bootstrapper = $this->container->get($name);
            $bootstrapper->bootstrap();
        }
    }
}