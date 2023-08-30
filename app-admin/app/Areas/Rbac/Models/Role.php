<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;

#[Table('rbac_role')]
class Role extends Model
{
    public $role_id;
    public $role_name;
    public $display_name;
    public $enabled;
    public $permissions;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

    public function rules(): array
    {
        return [
            'role_name'    => ['lower', 'length' => '3-15', 'unique'],
            'display_name' => ['trim', 'lower', 'length' => '3-15', 'unique'],
            'enabled'      => 'bool',
        ];
    }
}