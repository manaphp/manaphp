<?php

namespace App\Areas\Rbac\Models;

use ManaPHP\Db\Model;

class Permission extends Model
{
    const TYPE_PENDING = 0;
    const TYPE_PUBLIC = 1;
    const TYPE_INTERNAL = 2;
    const TYPE_PRIVATE = 3;

    public $permission_id;
    public $type;
    public $enabled;
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
            'type' => 'const',
            'enabled' => 'int',
            'description' => ['length' => '0-128']
        ];
    }
}
