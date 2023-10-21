<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\EventDispatcherInterface;

abstract class AbstractHandler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected AccessLogInterface $accessLog;
    #[Autowired] protected ServerInterface $httpServer;
}