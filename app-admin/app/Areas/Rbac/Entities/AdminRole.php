<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Entities;

use App\Entities\Entity;
use ManaPHP\Persistence\Attribute\Fillable;
use ManaPHP\Persistence\Attribute\Table;
use ManaPHP\Validating\Constraint\Attribute\Unique;

#[Table('rbac_admin_role')]
#[Fillable([])]
class AdminRole extends Entity
{
    public int $id;
    #[Unique(['role_id'])]
    public int $admin_id;
    public string $admin_name;
    public int $role_id;
    public string $role_name;
    public string $creator_name;
    public int $created_time;
}