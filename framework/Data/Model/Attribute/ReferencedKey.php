<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ReferencedKey
{
    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function get(): string
    {
        return $this->name;
    }
}