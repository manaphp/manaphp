<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    public string|array $roles;

    public function __construct(string|array $roles)
    {
        $this->roles = $roles;
    }
}