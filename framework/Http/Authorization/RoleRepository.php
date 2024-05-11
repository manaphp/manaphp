<?php
declare(strict_types=1);

namespace ManaPHP\Http\Authorization;

class RoleRepository implements RoleRepositoryInterface
{
    public function getPermissions(string $role): ?string
    {
        return null;
    }
}