<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Transient implements Transiently
{

}