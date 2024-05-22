<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Event;

use ManaPHP\Persistence\Entity;

interface EntityEventInterface
{
    public function getEntity(): Entity;

    public function getOriginal(): ?Entity;

    public function hasChanged(array $fields): bool;
}