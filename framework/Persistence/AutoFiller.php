<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Identifying\IdentityInterface;
use ReflectionNamedType;
use ReflectionProperty;

class AutoFiller implements AutoFillerInterface
{
    #[Autowired] protected IdentityInterface $identity;

    #[Autowired] protected array $created_time = ['created_time', 'created_at'];
    #[Autowired] protected array $created_by = ['created_by'];
    #[Autowired] protected array $creator_id = ['creator_id'];
    #[Autowired] protected array $creator_name = ['creator_name'];

    #[Autowired] protected array $updated_time = ['updated_time', 'updated_at'];
    #[Autowired] protected array $updated_by = ['updated_by'];
    #[Autowired] protected array $updator_id = ['updator_id'];
    #[Autowired] protected array $updator_name = ['updator_name'];

    protected function findField(Entity $entity, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (property_exists($entity, $field)) {
                return $field;
            }
        }

        return null;
    }

    public function setTime(Entity $entity, string $field, int $timestamp): void
    {
        $rProperty = new ReflectionProperty($entity, $field);
        if (($rType = $rProperty->getType()) && $rType instanceof ReflectionNamedType) {
            $type = $rType->getName();
            $entity->$field = $type === 'int' ? $timestamp : date('Y-m-d H:i:s');
        }
    }

    protected function setBy(Entity $entity, string $field, int $id, string $name): void
    {
        $rProperty = new ReflectionProperty($entity, $field);
        if (($rType = $rProperty->getType()) && $rType instanceof ReflectionNamedType) {
            $type = $rType->getName();
            $entity->$field = $type === 'int' ? $id : $name;
        }
    }

    public function fillCreated(Entity $entity): void
    {
        $timestamp = time();
        $user_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $user_name = $this->identity->isGuest() ? '' : $this->identity->getName();

        $created_time = $this->findField($entity, $this->created_time);
        if ($created_time !== null && !isset($entity->$created_time)) {
            $this->setTime($entity, $created_time, $timestamp);
        }

        $created_by = $this->findField($entity, $this->created_by);
        if ($created_by !== null) {
            if (!isset($entity->$created_by)) {
                $this->setBy($entity, $created_by, $user_id, $user_name);
            }
        } else {
            $creator_id = $this->findField($entity, $this->creator_id);
            if ($creator_id !== null && !isset($entity->$creator_id)) {
                $entity->$creator_id = $user_id;
            }

            $creator_name = $this->findField($entity, $this->creator_name);
            if ($creator_name !== null && !isset($entity->$creator_name)) {
                $entity->$creator_name = $user_name;
            }
        }

        $updated_time = $this->findField($entity, $this->updated_time);
        if ($updated_time !== null && !isset($entity->$updated_time)) {
            $this->setTime($entity, $updated_time, $timestamp);
        }

        $updated_time = $this->findField($entity, $this->updated_time);
        if ($updated_time !== null && !isset($entity->$updated_time)) {
            $this->setTime($entity, $updated_time, $timestamp);
        }

        $updated_by = $this->findField($entity, $this->updated_by);
        if ($updated_by !== null) {
            if (!isset($entity->$updated_by)) {
                $this->setBy($entity, $updated_by, $user_id, $user_name);
            }
        } else {
            $updator_id = $this->findField($entity, $this->updator_id);
            if ($updator_id !== null && !isset($entity->$updator_id)) {
                $entity->$updator_id = $user_id;
            }

            $updator_name = $this->findField($entity, $this->updator_name);
            if ($updator_name !== null && !isset($entity->$updator_name)) {
                $entity->$updator_name = $user_name;
            }
        }
    }

    public function fillUpdated(Entity $entity): void
    {
        $timestamp = time();
        $user_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $user_name = $this->identity->isGuest() ? '' : $this->identity->getName();

        $updated_time = $this->findField($entity, $this->updated_time);
        if ($updated_time !== null) {
            $this->setTime($entity, $updated_time, $timestamp);
        }

        $updated_by = $this->findField($entity, $this->updated_by);
        if ($updated_by !== null) {
            $this->setBy($entity, $updated_by, $user_id, $user_name);
        } else {
            $updator_id = $this->findField($entity, $this->updator_id);
            if ($updator_id !== null) {
                $entity->$updator_id = $user_id;
            }

            $updator_name = $this->findField($entity, $this->updator_name);
            if ($updator_name !== null) {
                $entity->$updator_name = $user_name;
            }
        }
    }
}