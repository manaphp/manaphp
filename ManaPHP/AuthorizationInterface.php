<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\AuthorizationInterface
 *
 * @package authorization
 */
interface AuthorizationInterface
{
    /**
     * Check whether a user is allowed to access a permission
     *
     * @param string $permission
     * @param string $role
     *
     * @return bool
     */
    public function isAllowed($permission = null, $role = null);

    /**
     * @param string $permission
     * @param string $role
     *
     * @throws \ManaPHP\Identity\NoCredentialException
     * @throws \ManaPHP\Exception\ForbiddenException
     */
    public function authorize($permission = null, $role = null);
}