<?php

namespace App\Areas\Rbac\Models;

use App\Models\Model;

class AdminRole extends Model
{
    public $id;
    public $admin_id;
    public $admin_name;
    public $role_id;
    public $role_name;
    public $creator_name;
    public $created_time;

    public function table()
    {
        return 'rbac_admin_role';
    }

    public function safeFields()
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