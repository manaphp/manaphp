<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Models;

use App\Models\Model;
use ManaPHP\Data\Model\Attribute\Table;

#[Table('rbac_permission')]
class Permission extends Model
{
    public $permission_id;
    public $path;
    public $display_name;
    public $created_time;
    public $updated_time;

    public function rules(): array
    {
        return [
            'display_name' => ['length' => '0-128']
        ];
    }
}
