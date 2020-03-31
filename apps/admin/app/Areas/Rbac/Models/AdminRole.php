<?php
namespace App\Areas\Rbac\Models;

use ManaPHP\Db\Model;

class AdminRole extends Model
{
    public $id;
    public $admin_id;
    public $admin_name;
    public $role_id;
    public $role_name;
    public $creator_name;
    public $created_time;

    public function getTable()
    {
        return 'rbac_admin_role';
    }

    public function getSafeFields()
    {
        return [];
    }

    public function rules()
    {
        return [
            'admin_id' => ['unique' => 'role_id']
        ];
    }
}