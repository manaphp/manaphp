<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;
use function is_string;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    public const ADMIN = 'admin';
    public const USER = 'user';
    public const GUEST = 'guest';

    public array $roles;

    public function __construct(string|array $roles = [])
    {
        $this->roles = is_string($roles) ? [$roles] : $roles;
    }

    public function isGrantable(): bool
    {
        return !in_array(self::GUEST, $this->roles, true)
            && !in_array(self::USER, $this->roles, true);
    }
}