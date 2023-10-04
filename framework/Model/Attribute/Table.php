<?php
declare(strict_types=1);

namespace ManaPHP\Model\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(public string $name)
    {
    }
}