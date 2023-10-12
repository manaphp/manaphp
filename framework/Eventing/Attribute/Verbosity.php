<?php
declare(strict_types=1);

namespace ManaPHP\Eventing\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Verbosity
{
    public const HIGH = 3;
    public const MEDIUM = 2;
    public const LOW = 1;

    public function __construct(public int $verbosity)
    {

    }
}