<?php
declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Eventing\Attribute\Verbosity;

#[Verbosity(Verbosity::HIGH)]
class PoolPopped extends PoolBase
{

}