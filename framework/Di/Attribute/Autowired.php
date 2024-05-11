<?php
declare(strict_types=1);

namespace ManaPHP\Di\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Autowired
{

}