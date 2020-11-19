<?php

namespace App\Areas\Rbac\Models;

use App\Models\Model;

class Permission extends Model
{
    public $permission_id;
    public $path;
    public $display_name;
    public $created_time;
    public $updated_time;

    public function getTable()
    {
        return 'rbac_permission';
    }

    public function rules()
    {
        return [
            'display_name' => ['length' => '0-128']
        ];
    }
}
