<?php
namespace App\Areas\Rbac\Models;

use ManaPHP\Db\Model;

class RolePermission extends Model
{
    public $id;
    public $role_id;
    public $permission_id;
    public $creator_name;
    public $created_time;

    public function getSource($context = null)
    {
        return 'rbac_role_permission';
    }

    public function getSafeFields()
    {
        return [];
    }

    public function relations()
    {
        return ['permission' => Permission::class];
    }
}