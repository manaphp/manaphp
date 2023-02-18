<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\RequestInterface        $request
 * @property-read \ManaPHP\Http\ResponseInterface       $response
 * @property-read \ManaPHP\Http\RouterInterface         $router
 * @property-read \ManaPHP\Http\HandlerInterface        $httpHandler
 * @property-read \ManaPHP\Http\GlobalsInterface        $globals
 * @property-read \ManaPHP\Http\Filter\ManagerInterface $filterManager
 */
abstract class AbstractServer extends Component implements ServerInterface
{
    protected string $host;
    protected int $port;

    public function __construct(string $host = '0.0.0.0', int $port = 9501)
    {
        $this->host = $host;
        $this->port = $port;

        $this->filterManager->register();
    }
}