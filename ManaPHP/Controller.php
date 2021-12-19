<?php
declare(strict_types=1);

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

    public function invoke(string $action): mixed
    {
        return $this->invoker->invoke($this, $action . 'Action');
    }
}