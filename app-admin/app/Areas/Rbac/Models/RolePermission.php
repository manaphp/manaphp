<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Data\Model\Attribute\Fillable;
use ManaPHP\Data\Model\Attribute\Table;

#[Table('rbac_role_permission')]
#[Fillable([])]
class RolePermission extends Model
{
    public $id;
    public $role_id;
    public $permission_id;
    public $creator_name;
    public $created_time;
}