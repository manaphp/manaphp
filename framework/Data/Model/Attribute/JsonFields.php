<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class JsonFields
{
    protected array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function get(): array
    {
        return $this->fields;
    }
}