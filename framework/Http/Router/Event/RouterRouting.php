<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Event;

use ManaPHP\Http\RouterInterface;

class RouterRouting
{
    public function __construct(
        public RouterInterface $router
    ) {

    }
}