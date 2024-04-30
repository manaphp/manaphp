<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;
use function in_array;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    public ?string $role;

    public function __construct(string $role = null)
    {
        $this->role = $role === '*' ? 'guest' : $role;
    }

    public function isAllowed(array $roles): ?bool
    {
        if (in_array('admin', $roles, true)) {
            return true;
        } elseif ($this->role === null) {
            return null;
        } elseif ($this->role === 'guest') {
            return true;
        } elseif ($roles) {
            if ($this->role === 'user') {
                return true;
            } else {
                return in_array($this->role, $roles, true);
            }
        }

        return null;
    }
}