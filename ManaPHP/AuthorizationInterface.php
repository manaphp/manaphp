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
     * @param string $controllerClassName
     * @param string $action
     *
     * @return string
     */
    public function generatePath($controllerClassName, $action);

    /**
     * @param string $role
     * @param array  $explicit_permissions
     *
     * @return array
     */
    public function buildAllowed($role, $explicit_permissions = []);

    /**
     * @param string $role
     *
     * @return array
     */
    public function getAllowed($role);

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
     * @param string $role
     *
     * @throws \ManaPHP\Identity\NoCredentialException
     * @throws \ManaPHP\Exception\ForbiddenException
     */
    public function authorize($role = null);
}