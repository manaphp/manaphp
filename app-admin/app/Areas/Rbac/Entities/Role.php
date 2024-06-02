<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Entities;

use App\Entities\Entity;
use ManaPHP\Persistence\Attribute\Id;
use ManaPHP\Persistence\Attribute\Table;
use ManaPHP\Validating\Constraint\Attribute\Length;
use ManaPHP\Validating\Constraint\Attribute\Lowercase;
use ManaPHP\Validating\Constraint\Attribute\Trim;
use ManaPHP\Validating\Constraint\Attribute\Type;
use ManaPHP\Validating\Constraint\Attribute\Unique;

#[Table('rbac_role')]
class Role extends Entity
{
    #[Id]
    public int $role_id;

    #[Lowercase, Length(3, 15), Unique]
    public string $role_name;

    #[Trim, Lowercase, Length(3, 15), Unique]
    public string $display_name;

    public int $builtin;

    #[Type('bit')]
    public int $enabled;

    public string $permissions;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;
}