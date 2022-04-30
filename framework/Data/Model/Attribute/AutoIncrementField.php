<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Attribute;

use Attribute;

#[Attribute(\Attribute::TARGET_CLASS)]
class AutoIncrementField
{
    protected ?string $field;

    public function __construct(?string $field)
    {
        $this->field = $field;
    }

    public function get(): ?string
    {
        return $this->field;
    }
}