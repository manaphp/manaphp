<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;

#[Table('rbac_permission')]
class Permission extends Model
{
    public int $permission_id;
    public string $path;
    public string $display_name;
    public int $created_time;
    public int $updated_time;

    public function rules(): array
    {
        return [
            'display_name' => ['length' => '0-128']
        ];
    }
}
