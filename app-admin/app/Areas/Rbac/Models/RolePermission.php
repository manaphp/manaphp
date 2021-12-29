<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;

class RolePermission extends Model
{
    public $id;
    public $role_id;
    public $permission_id;
    public $creator_name;
    public $created_time;

    public function table(): string
    {
        return 'rbac_role_permission';
    }

    public function safeFields(): array
    {
        return [];
    }
}