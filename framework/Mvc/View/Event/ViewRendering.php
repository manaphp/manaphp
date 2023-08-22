<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Event;

use ManaPHP\Mvc\ViewInterface;

class ViewRendering
{
    public function __construct(
        public ViewInterface $view,
    ) {

    }
}