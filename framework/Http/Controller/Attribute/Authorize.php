<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;
use function is_string;
use function str_starts_with;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    public const ADMIN = 'admin';
    public const USER = 'user';
    public const GUEST = 'guest';

    public string|array $roles;

    public function __construct(string|array $roles = [])
    {
        if (is_string($roles)) {
            $this->roles = str_starts_with($roles, '@') ? $roles : [$roles];
        } else {
            $this->roles = $roles;
        }
    }
}