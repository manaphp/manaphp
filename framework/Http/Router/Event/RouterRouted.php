<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Event;

use ManaPHP\Http\RouterInterface;

class RouterRouted
{
    public function __construct(
        public RouterInterface $router
    ) {

    }
}