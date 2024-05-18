<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class NamingStrategy
{
    public const UNDERSCORE = 'ManaPHP\Persistence\UnderscoreNamingStrategy';

    public function __construct(public string $strategy)
    {

    }
}