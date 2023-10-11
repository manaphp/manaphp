<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Event;

use JsonSerializable;
use ManaPHP\Http\RouterInterface;

class RouterRouted implements JsonSerializable
{
    public function __construct(
        public RouterInterface $router
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            'uri'        => $this->router->getRewriteUri(),
            'matched'    => $this->router->wasMatched(),
            'area'       => $this->router->getArea(),
            'controller' => $this->router->getController(),
            'action'     => $this->router->getAction(),
            'params'     => $this->router->getParams(),
        ];
    }
}