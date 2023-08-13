<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
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

    protected string $host;
    protected int $port;

    public function __construct(string $host = '0.0.0.0', int $port = 9501)
    {
        $this->host = $host;
        $this->port = $port;

        $this->filterManager->register();
    }
}