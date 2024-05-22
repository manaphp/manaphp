<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Event;

use ManaPHP\Persistence\Entity;

class AbstractEntityEvent implements EntityEventInterface
{
    public function __construct(protected Entity $entity, protected ?Entity $original = null)
    {
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function getOriginal(): ?Entity
    {
        return $this->original;
    }

    public function hasChanged(array $fields): bool
    {
        if ($this->original === null) {
            return false;
        }

        $entity = $this->entity;
        $original = $this->original;
        foreach ($fields as $field) {
            if (isset($entity->$field) && $entity->$field !== $original->$field) {
                return true;
            }
        }

        return false;
    }
}