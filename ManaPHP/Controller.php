<?php

namespace ManaPHP;

use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Controller\InvokerInterface $invoker
 */
class Controller extends Component implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    /**
     * @param string $action
     *
     * @return mixed
     */
    public function invoke($action)
    {
        return $this->invoker->invoke($this, $action . 'Action');
    }
}