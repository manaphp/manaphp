<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Fillable;
use ManaPHP\Model\Attribute\Table;

#[Table('rbac_admin_role')]
#[Fillable([])]
class AdminRole extends Model
{
    public $id;
    public $admin_id;
    public $admin_name;
    public $role_id;
    public $role_name;
    public $creator_name;
    public $created_time;

    public function rules(): array
    {
        return [
            'admin_id' => ['unique' => 'role_id']
        ];
    }
}