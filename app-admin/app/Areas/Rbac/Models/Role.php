<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;
use ManaPHP\Validating\Rule\Attribute\Boolean;
use ManaPHP\Validating\Rule\Attribute\Length;
use ManaPHP\Validating\Rule\Attribute\Lower;
use ManaPHP\Validating\Rule\Attribute\Trim;
use ManaPHP\Validating\Rule\Attribute\Unique;

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
            'role_name'    => [new Lower(), new Length(3, 15), new Unique()],
            'display_name' => [new Trim(), new Lower(), new Length(3, 15), new Unique()],
            'enabled'      => new Boolean(),
        ];
    }
}