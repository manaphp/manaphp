<?php

namespace ManaPHP;

use ManaPHP\Http\Dispatcher\NotFoundActionException;
use ManaPHP\Logging\Logger\LogCategorizable;
use Throwable;

/**
 * @property-read \ManaPHP\InvokerInterface $invoker
 */
class Controller extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    /**
     * @param string $action
     *
     * @throws NotFoundActionException
     */
    public function validateInvokable($action)
    {
        $method = $action . 'Action';

        if (!in_array($method, get_class_methods($this), true)) {
            throw new NotFoundActionException(['`%s::%s` method does not exist', static::class, $method]);
        }
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function isInvokable($action)
    {
        try {
            $this->validateInvokable($action);
            return true;
        } catch (Throwable $throwable) {
            return false;
        }
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