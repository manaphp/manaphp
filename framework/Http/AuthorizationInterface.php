<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface AuthorizationInterface
{
    public function getPermission(string $controller, string $action): string;

    public function isAllowed(string $permission, ?array $roles = null): bool;

    public function authorize(): void;
}