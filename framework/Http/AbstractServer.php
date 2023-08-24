<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractServer implements ServerInterface
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected RouterInterface $router;
    #[Inject] protected HandlerInterface $httpHandler;
    #[Inject] protected GlobalsInterface $globals;

    #[Value] protected string $host = '0.0.0.0';
    #[Value] protected int $port = 9501;
}