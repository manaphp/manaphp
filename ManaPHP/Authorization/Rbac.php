<?php
namespace ManaPHP\Authorization;

use ManaPHP\Authorization\Rbac\Models\Permission;
use ManaPHP\AuthorizationInterface;
use ManaPHP\Component;

class Rbac extends Component implements AuthorizationInterface
{
    /**
     * @var string
     */
    protected $_userRoleModel = 'ManaPHP\Authorization\Rbac\Models\UserRole';

    /**
     * @var string
     */
    protected $_rolePermissionModel = 'ManaPHP\Authorization\Rbac\Models\RolePermission';

    /**
     * @var string
     */
    protected $_permissionModel = 'ManaPHP\Authorization\Rbac\Models\Permission';

    /**
     * Rbac constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['userRoleMode'])) {
            $this->_userRoleModel = $options['userRoleMode'];
        }

        if (isset($options['rolePermissionModel'])) {
            $this->_rolePermissionModel = $options['rolePermissionModel'];
        }

        if (isset($options['permissionModel'])) {
            $this->_permissionModel = $options['permissionModel'];
        }
    }

    /**
     * @param string $permissionName
     *
     * @return array
     * @throws \ManaPHP\Authorization\Exception|\ManaPHP\Mvc\Model\Exception
     */
    protected function _getPermission($permissionName)
    {
        $rows = $this->modelsManager->createBuilder()
            ->columns('permission_id, permission_type')
            ->addFrom($this->_permissionModel)
            ->where('permission_name', $permissionName)
            ->execute();

        if (count($rows) === 0) {
            throw new Exception('Permission is not exists: ' . $permissionName);
        }

        return $rows[0];
    }

    /**
     * @param int $permissionId
     *
     * @return array
     * @throws \ManaPHP\Authorization\Exception|\ManaPHP\Mvc\Model\Exception
     */
    protected function _getRolesByPermission($permissionId)
    {
        $rows = $this->modelsManager->createBuilder()
            ->columns('role_id')
            ->addFrom($this->_rolePermissionModel)
            ->where('permission_id', $permissionId)
            ->execute();

        $roleIds = [];
        foreach ($rows as $row) {
            $roleIds[] = $row['role_id'];
        }

        return $roleIds;
    }

    /**
     * @param int|string $userId
     *
     * @return array
     * @throws \ManaPHP\Authorization\Exception|\ManaPHP\Mvc\Model\Exception
     */
    protected function _getRolesByUser($userId)
    {
        $rows = $this->modelsManager->createBuilder()
            ->columns('role_id')
            ->addFrom($this->_rolePermissionModel)
            ->where('user_id', $userId)
            ->execute();
        $roleIds = [];
        foreach ($rows as $row) {
            $roleIds[] = $row['role_id'];
        }

        return $roleIds;
    }

    /**
     * @param string $permissionName
     *
     * @return string
     * @throws \ManaPHP\Authorization\Exception
     */
    protected function _getStandardPermissionName($permissionName)
    {
        $parts = explode('::', $permissionName);

        switch (count($parts)) {
            case 1:
                $parts = [$this->dispatcher->getModuleName(), $this->dispatcher->getControllerName(), $parts[0]];
                break;
            case 2:
                $parts = [$this->dispatcher->getModuleName(), $parts[0], $parts[1]];
                break;
            case 3:
                break;
            default:
                throw new Exception('Permission name format is invalid: ' . $permissionName);
        }

        return implode('::', $parts);
    }

    /**
     * @param string      $permissionName
     * @param string|null $userId
     *
     * @return bool
     * @throws \ManaPHP\Authorization\Exception
     */
    public function isAllowed($permissionName, $userId = null)
    {
        $permissionName = $this->_getStandardPermissionName($permissionName);

        if ($userId === null) {
            $userId = $this->userIdentity->getId();
        }

        $permission = $this->_getPermission($permissionName);

        $permissionId = (int)$permission['permission_id'];
        $permissionType = (int)$permission['permission_type'];

        if ($permissionType === Permission::TYPE_PUBLIC) {
            return true;
        } elseif ($permissionType === Permission::TYPE_INTERNAL) {
            /** @noinspection IsEmptyFunctionUsageInspection */
            return (!empty($userId));
        } elseif ($permissionType === Permission::TYPE_PENDING) {
            throw new Exception('Permission type is not configured: ' . $permissionName);
        }

        $rolesByPermission = $this->_getRolesByPermission($permissionId);

        $rolesByUser = $this->_getRolesByUser($userId);

        if (array_intersect($rolesByUser, $rolesByPermission)) {
            return true;
        } else {
            return false;
        }
    }
}