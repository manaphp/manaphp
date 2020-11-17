<?php

namespace ManaPHP\Http;

interface AuthorizationInterface
{
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
     * @return string
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
     * @throws \ManaPHP\Identity\NoCredentialException
     * @throws \ManaPHP\Exception\ForbiddenException
     */
    public function authorize();
}