<?php

namespace App\Admin\Areas\Rbac\Models;

use ManaPHP\Db\Model;
use ManaPHP\Model\Relation;

class Permission extends Model
{
    const TYPE_PENDING = 0;
    const TYPE_PUBLIC = 1;
    const TYPE_INTERNAL = 2;
    const TYPE_PRIVATE = 3;
    const TYPE_DISABLED = 4;

    public $permission_id;
    public $type;
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
            'description' => ['length' => '0-128']
        ];
    }
}
