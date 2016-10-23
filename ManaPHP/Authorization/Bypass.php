<?php
namespace ManaPHP\Authorization;

use ManaPHP\AuthorizationInterface;

/**
 * Class ManaPHP\Authorization\Bypass
 *
 * @package ManaPHP\Authorization
 */
class Bypass implements AuthorizationInterface
{
    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     *
     * @return bool
     */
    public function authorize($dispatcher)
    {
        return true;
    }

    /**
     * @param string $permission
     * @param string $userId
     *
     * @return bool
     */
    public function isAllowed($permission, $userId = null)
    {
        return true;
    }
}