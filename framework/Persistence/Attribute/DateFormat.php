<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DateFormat
{
    protected string $format;

    public function __construct(string $format)
    {
        $this->format = $format;
    }

    public function get(): string
    {
        return $this->format;
    }
}