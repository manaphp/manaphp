<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;

#[Table('rbac_role')]
class Role extends Model
{
    public int $role_id;
    public string $role_name;
    public string $display_name;
    public int $enabled;
    public string $permissions;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;

    public function rules(): array
    {
        return [
            'role_name'    => ['lower', 'length' => '3-15', 'unique'],
            'display_name' => ['trim', 'lower', 'length' => '3-15', 'unique'],
            'enabled'      => 'bool',
        ];
    }
}