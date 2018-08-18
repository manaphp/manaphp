<?php
namespace App\Areas\Rbac\Models;

use App\Models\Admin;
use ManaPHP\Db\Model;
use ManaPHP\Model\Relation;

class AdminRole extends Model
{
    public $id;
    public $admin_id;
    public $admin_name;
    public $role_id;
    public $role_name;
    public $creator_name;
    public $created_time;

    public function getSource($context = null)
    {
        return 'rbac_admin_role';
    }

    public function getSafeFields()
    {
        return [];
    }

    public function relations()
    {
        return ['admins' => [Admin::class, Relation::TYPE_HAS_MANY_TO_MANY, 'admin_id', 'role_id']];
    }
}