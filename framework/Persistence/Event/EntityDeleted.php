<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Persistence\Entity;

#[Verbosity(Verbosity::HIGH)]
class EntityDeleted
{
    public function __construct(public Entity $entity)
    {

    }
}