<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Event;

use ManaPHP\Eventing\Attribute\Verbosity;

#[Verbosity(Verbosity::LOW)]
class EntityCreating extends AbstractEntityEvent
{
}