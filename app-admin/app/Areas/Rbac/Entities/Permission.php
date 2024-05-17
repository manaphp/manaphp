<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Entities;

use App\Entities\Entity;
use ManaPHP\Persistence\Attribute\Id;
use ManaPHP\Persistence\Attribute\Table;
use ManaPHP\Validating\Constraint\Attribute\MaxLength;

#[Table('rbac_permission')]
class Permission extends Entity
{
    #[Id]
    public int $permission_id;

    public string $handler;

    #[MaxLength(128)]
    public string $display_name;

    public int $created_time;
    public int $updated_time;
}
