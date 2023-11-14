<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Fillable;
use ManaPHP\Model\Attribute\Table;
use ManaPHP\Validating\Constraint\Attribute\Unique;

#[Table('rbac_admin_role')]
#[Fillable([])]
class AdminRole extends Model
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