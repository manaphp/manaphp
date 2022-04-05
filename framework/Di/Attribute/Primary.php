<?php
declare(strict_types=1);

namespace ManaPHP\Di\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Primary
{
    public string $definition;

    public function __construct($definition)
    {
        $this->definition = $definition;
    }
}