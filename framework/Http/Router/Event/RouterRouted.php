<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\Router\MatcherInterface;
use ManaPHP\Http\RouterInterface;

#[Verbosity(Verbosity::LOW)]
class RouterRouted implements JsonSerializable
{
    public function __construct(
        public RouterInterface $router,
        public ?MatcherInterface $matcher,
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            'uri'     => $this->router->getRewriteUri(),
            'handler' => $this->matcher?->getHandler(),
            'params'  => $this->matcher?->getParams(),
        ];
    }
}