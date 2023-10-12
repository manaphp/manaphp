<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Mvc\ViewInterface;

#[Verbosity(Verbosity::HIGH)]
class ViewRendered
{
    public function __construct(
        public ViewInterface $view,
    ) {

    }
}