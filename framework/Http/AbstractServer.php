<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Event\EventTrait;
use ManaPHP\Http\Filter\ManagerInterface;

abstract class AbstractServer extends Component implements ServerInterface
{
    use EventTrait;

    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected RouterInterface $router;
    #[Inject] protected HandlerInterface $httpHandler;
    #[Inject] protected GlobalsInterface $globals;
    #[Inject] protected ManagerInterface $filterManager;

    #[Value] protected string $host = '0.0.0.0';
    #[Value] protected int $port = 9501;

    public function __construct()
    {
        $this->filterManager->register();
    }
}