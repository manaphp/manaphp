<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Entities;

use App\Entities\Entity;
use ManaPHP\Persistence\Attribute\Fillable;
use ManaPHP\Persistence\Attribute\Table;

#[Table('rbac_role_permission')]
#[Fillable([])]
class RolePermission extends Entity
{
    public int $id;
    public int $role_id;
    public int $permission_id;
    public string $creator_name;
    public int $created_time;
}