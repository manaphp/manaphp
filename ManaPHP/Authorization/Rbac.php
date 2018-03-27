<?php
namespace ManaPHP\Authorization;

use ManaPHP\Authorization\Rbac\Exception as RbacException;
use ManaPHP\Authorization\Rbac\Models\Permission;
use ManaPHP\AuthorizationInterface;
use ManaPHP\Component;

/**
 * Class ManaPHP\Authorization\Rbac
 *
 * @package rbac
 *
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
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
        if (isset($options['userRoleModel'])) {
            $this->_userRoleModel = $options['userRoleModel'];
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
                list($action) = $parts;
                break;
            case 2:
                $module = $this->dispatcher->getModuleName();
                list($controller, $action) = $parts;
                break;
            case 3:
                list($module, $controller, $action) = $parts;
                break;
            default:
                throw new RbacException(['`:permission` has too many parts'/**m059345500bb0de141*/, 'permission' => $permissionName]);
        }

        return [$module, $controller, $action];
    }

    /**
     * @param int $permissionId
     *
     * @return array
     */
    protected function _getRolesByPermissionId($permissionId)
    {
        /**
         * @var \ManaPHP\Authorization\Rbac\Models\RolePermission $model
         */
        $model = new $this->_rolePermissionModel;
        $roles = [];
        foreach ($model::find(['permission_id' => $permissionId]) as $item) {
            $roles[] = $item->role_id;
        }

        return $roles;
    }

    /**
     * @param string $userId
     *
     * @return array
     */
    protected function _getRolesByUserId($userId)
    {
        /**
         * @var \ManaPHP\Authorization\Rbac\Models\UserRole $model
         */
        $model = new $this->_userRoleModel();
        $roles = [];
        foreach ($model::find(['user_id' => $userId]) as $item) {
            $roles[] = $item->role_id;
        }

        return $roles;
    }

    /**
     * @param string $name
     *
     * @return false|\ManaPHP\Authorization\Rbac\Models\Permission
     * @throws \ManaPHP\Authorization\Rbac\Exception
     */
    protected function _getPermissionByName($name)
    {
        list($module, $controller, $action) = $this->_parsePermissionName($name);
        /**
         * @var \ManaPHP\Authorization\Rbac\Models\Permission $model
         */
        $model = new $this->_permissionModel();
        return $model::findFirst(['module' => $module, 'controller' => $controller, 'action' => $action]);
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
        $userId = $userId ?: $this->userIdentity->getId();

        $permission = $this->_getPermissionByName($permissionName);
        if (!$permission) {
            throw new RbacException(['`:permission` permission is not exists'/**m06ab9af781c2de7f2*/, 'permission' => $permissionName]);
        }

        switch ($permission->permission_type) {
            case Permission::TYPE_PENDING:
                throw new RbacException(['`:permission` type is not assigned'/**m0ac1449c071933ff6*/, 'permission' => $permission->description]);
            case Permission::TYPE_PUBLIC:
                return true;
            case Permission::TYPE_INTERNAL:
                return !empty($userId);
            case Permission::TYPE_DISABLED:
                return false;
            case Permission::TYPE_PRIVATE:
                $rolesByPermissionId = $this->_getRolesByPermissionId($permission->permission_id);
                $rolesByUserId = $this->_getRolesByUserId($userId);

                return (bool)array_intersect($rolesByPermissionId, $rolesByUserId);
            default:
                throw new RbacException(['`:permission` type is not recognized', 'permission' => $permissionName]);
        }
    }
}