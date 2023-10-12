<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\RouterInterface;

#[Verbosity(Verbosity::HIGH)]
class RouterRouting
{
    public function __construct(
        public RouterInterface $router
    ) {

    }
}