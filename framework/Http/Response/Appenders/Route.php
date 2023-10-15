<?php
declare(strict_types=1);

namespace ManaPHP\Http\Response\Appenders;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\ResponseInterface;

class Route implements AppenderInterface
{
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected ?bool $enabled;

    public function append(RequestInterface $request, ResponseInterface $response): void
    {
        if ($this->enabled ?? $this->config->get('env') === 'dev') {
            $controller = $this->dispatcher->getControllerInstance();
            $action = $this->dispatcher->getAction();

            if (is_object($controller)) {
                $response->setHeader(
                    'X-Router-Route', $controller::class . '::' . $action . 'Action'
                );
            }
        }
    }
}