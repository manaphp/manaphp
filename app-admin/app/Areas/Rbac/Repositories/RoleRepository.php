<?php
declare(strict_types=1);

namespace App\Areas\Rbac\Repositories;

use App\Areas\Rbac\Entities\Role;
use ManaPHP\Db\Repository;
use ManaPHP\Http\Authorization\RoleRepositoryInterface;

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