<?php
declare(strict_types=1);

namespace ManaPHP\Http\Authorization;

interface RoleRepositoryInterface
{
    public function getPermissions(string $role): ?string;
}