<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Fillable;
use ManaPHP\Model\Attribute\Table;

#[Table('rbac_role_permission')]
#[Fillable([])]
class RolePermission extends Model
{
    public int $id;
    public int $role_id;
    public int $permission_id;
    public string $creator_name;
    public int $created_time;
}