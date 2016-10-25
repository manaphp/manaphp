<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

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

    /**
     * @return string
     */
    public function getSource()
    {
        return 'rbac_role_permission';
    }
}