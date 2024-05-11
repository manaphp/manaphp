<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Repositories;

use App\Areas\Rbac\Models\Role;
use ManaPHP\Http\Authorization\RoleRepositoryInterface;
use ManaPHP\Persistence\Repository;

/**
 * @extends Repository<Role>
 */
class RoleRepository extends Repository implements RoleRepositoryInterface
{
    public function getPermissions(string $role): ?string
    {
        return $this->value(['role_name' => $role], 'permissions');
    }
}