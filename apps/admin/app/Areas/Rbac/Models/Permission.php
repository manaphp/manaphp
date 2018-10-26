<?php

namespace App\Areas\Rbac\Models;

use ManaPHP\Db\Model;

class Permission extends Model
{
    public $permission_id;
    public $path;
    public $description;
    public $created_time;
    public $updated_time;

    public function getSource($context = null)
    {
        return 'rbac_permission';
    }

    public function rules()
    {
        return [
            'description' => ['length' => '0-128']
        ];
    }
}
