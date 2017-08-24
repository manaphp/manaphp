<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Db\Model;

/**
 * Class ManaPHP\Authorization\Rbac\Models\RolePermission
 *
 * @package rbac\models
 */
class RolePermission extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $role_id;

    /**
     * @var int
     */
    public $permission_id;

    /**
     * @var int
     */
    public $created_time;

    public static function getSource($context = null)
    {
        return 'rbac_role_permission';
    }
}