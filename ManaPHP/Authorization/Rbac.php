<?php
namespace ManaPHP\Authorization;

use ManaPHP\Authorization\Rbac\Exception as RbacException;
use ManaPHP\Authorization\Rbac\Models\Permission;
use ManaPHP\AuthorizationInterface;
use ManaPHP\Component;

/**
 * CREATE TABLE `rbac_role` (
 * `role_id` int(11) NOT NULL AUTO_INCREMENT,
 * `role_name` char(64) CHARACTER SET latin1 NOT NULL,
 * `description` char(128) CHARACTER SET latin1 NOT NULL,
 * `created_time` int(11) NOT NULL,
 * PRIMARY KEY (`role_id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 *
 *
 * CREATE TABLE `rbac_permission` (
 * `permission_id` int(11) NOT NULL AUTO_INCREMENT,
 * `permission_type` tinyint(4) NOT NULL,
 * `module` char(32) NOT NULL,
 * `controller` char(32) NOT NULL,
 * `action` char(32) NOT NULL,
 * `description` char(128) NOT NULL,
 * `created_time` int(11) NOT NULL,
 * PRIMARY KEY (`permission_id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 *
 *
 * CREATE TABLE `rbac_role_permission` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `role_id` int(11) NOT NULL,
 * `permission_id` int(11) NOT NULL,
 * `created_time` int(11) NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 *
 * CREATE TABLE `rbac_user_role` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `user_id` int(11) NOT NULL,
 * `role_id` int(11) NOT NULL,
 * `created_time` int(11) NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 *
 *
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\Mvc\Model\ManagerInterface           $modelsManager
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 */
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
     * @throws \ManaPHP\Authorization\Rbac\Exception
     */
    protected function _parsePermissionName($permissionName)
    {
        $parts = explode('::', $permissionName);

        switch (count($parts)) {
            case 1:
                $module = $this->dispatcher->getModuleName();
                $controller = $this->dispatcher->getControllerName();
                $action = $parts[0];
                break;
            case 2:
                $module = $this->dispatcher->getModuleName();
                $controller = $parts[0];
                /** @noinspection MultiAssignmentUsageInspection */
                $action = $parts[1];
                break;
            case 3:
                $module = $parts[0];
                /** @noinspection MultiAssignmentUsageInspection */
                $controller = $parts[1];
                /** @noinspection MultiAssignmentUsageInspection */
                $action = $parts[2];
                break;
            default:
                throw new RbacException('`:permission` has too many parts'/**m059345500bb0de141*/, ['permission' => $permissionName]);
        }

        $r = [$module, $controller, $action];
        return $r;
    }

    /**
     * @param string $permissionName
     *
     * @return array
     * @throws \ManaPHP\Authorization\Rbac\Exception
     */
    protected function _getPermission($permissionName)
    {
        list($module, $controller, $action) = $this->_parsePermissionName($permissionName);

        $rows = $this->modelsManager->createBuilder()
            ->columns('permission_id, permission_type')
            ->addFrom($this->_permissionModel)
            ->where('module', $module)
            ->where('controller', $controller)
            ->where('action', $action)
            ->execute();

        if (count($rows) === 0) {
            throw new RbacException('`:permission` is not exists'/**m06ab9af781c2de7f2*/, ['permission' => $permissionName]);
        }

        return $rows[0];
    }

    /**
     * @param int $permissionId
     *
     * @return array
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
     * @param string $userId
     *
     * @return bool
     * @throws \ManaPHP\Authorization\Rbac\Exception
     */
    public function isAllowed($permissionName, $userId = null)
    {
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
            throw new RbacException('`:permission` type is not assigned'/**m0ac1449c071933ff6*/, ['permission' => $permissionName]);
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