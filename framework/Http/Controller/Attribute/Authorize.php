<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;
use function in_array;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    public const ADMIN = 'admin';
    public const USER = 'user';
    public const GUEST = 'guest';

    public ?string $role;

    public function __construct(string $role = null)
    {
        $this->role = $role === '*' ? self::GUEST : $role;
    }

    public function isAllowed(array $roles): ?bool
    {
        if (in_array(self::ADMIN, $roles, true)) {
            return true;
        } elseif ($this->role === null) {
            return null;
        } elseif ($this->role === self::GUEST) {
            return true;
        } elseif ($roles) {
            if ($this->role === self::USER) {
                return true;
            } else {
                return in_array($this->role, $roles, true);
            }
        }

        return null;
    }
}