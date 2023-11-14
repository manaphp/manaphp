<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;
use ManaPHP\Validating\Constraint\Attribute\Length;
use ManaPHP\Validating\Constraint\Attribute\Lowercase;
use ManaPHP\Validating\Constraint\Attribute\Trim;
use ManaPHP\Validating\Constraint\Attribute\Type;
use ManaPHP\Validating\Constraint\Attribute\Unique;

#[Table('rbac_role')]
class Role extends Model
{
    public int $role_id;
    #[Lowercase, Length(3, 15), Unique]
    public string $role_name;
    #[Trim, Lowercase, Length(3, 15), Unique]
    public string $display_name;
    #[Type('bit')]
    public int $enabled;
    public string $permissions;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;
}