<?php
declare(strict_types=1);

namespace ManaPHP\Model;

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

    protected function findField(ModelInterface $model, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (property_exists($model, $field)) {
                return $field;
            }
        }

        return null;
    }

    public function setTime(ModelInterface $model, string $field, int $timestamp): void
    {
        $rProperty = new ReflectionProperty($model, $field);
        if (($rType = $rProperty->getType()) && $rType instanceof ReflectionNamedType) {
            $type = $rType->getName();
            $model->$field = $type === 'int' ? $timestamp : date('Y-m-d H:i:s');
        }
    }

    protected function setBy(ModelInterface $model, string $field, int $id, string $name): void
    {
        $rProperty = new ReflectionProperty($model, $field);
        if (($rType = $rProperty->getType()) && $rType instanceof ReflectionNamedType) {
            $type = $rType->getName();
            $model->$field = $type === 'int' ? $id : $name;
        }
    }

    public function fillCreated(ModelInterface $model): void
    {
        $timestamp = time();
        $user_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $user_name = $this->identity->isGuest() ? '' : $this->identity->getName();

        $created_time = $this->findField($model, $this->created_time);
        if ($created_time !== null && !isset($model->$created_time)) {
            $this->setTime($model, $created_time, $timestamp);
        }

        $created_by = $this->findField($model, $this->created_by);
        if ($created_by !== null) {
            if (!isset($model->$created_by)) {
                $this->setBy($model, $created_by, $user_id, $user_name);
            }
        } else {
            $creator_id = $this->findField($model, $this->creator_id);
            if ($creator_id !== null && !isset($model->$creator_id)) {
                $model->$creator_id = $user_id;
            }

            $creator_name = $this->findField($model, $this->creator_name);
            if ($creator_name !== null && !isset($model->$creator_name)) {
                $model->$creator_name = $user_name;
            }
        }

        $updated_time = $this->findField($model, $this->updated_time);
        if ($updated_time !== null && !isset($model->$updated_time)) {
            $this->setTime($model, $updated_time, $timestamp);
        }

        $updated_time = $this->findField($model, $this->updated_time);
        if ($updated_time !== null && !isset($model->$updated_time)) {
            $this->setTime($model, $updated_time, $timestamp);
        }

        $updated_by = $this->findField($model, $this->updated_by);
        if ($updated_by !== null) {
            if (!isset($model->$updated_by)) {
                $this->setBy($model, $updated_by, $user_id, $user_name);
            }
        } else {
            $updator_id = $this->findField($model, $this->updator_id);
            if ($updator_id !== null && !isset($model->$updator_id)) {
                $model->$updator_id = $user_id;
            }

            $updator_name = $this->findField($model, $this->updator_name);
            if ($updator_name !== null && !isset($model->$updator_name)) {
                $model->$updator_name = $user_name;
            }
        }
    }

    public function fillUpdated(ModelInterface $model): void
    {
        $timestamp = time();
        $user_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $user_name = $this->identity->isGuest() ? '' : $this->identity->getName();

        $changed = $model->getChangedFields();

        $updated_time = $this->findField($model, $this->updated_time);
        if ($updated_time !== null && !in_array($updated_time, $changed, true)) {
            $this->setTime($model, $updated_time, $timestamp);
        }

        $updated_by = $this->findField($model, $this->updated_by);
        if ($updated_by !== null) {
            if (!in_array($updated_by, $changed, true)) {
                $this->setBy($model, $updated_by, $user_id, $user_name);
            }
        } else {
            $updator_id = $this->findField($model, $this->updator_id);
            if ($updator_id !== null && !in_array($updator_id, $changed, true)) {
                $model->$updator_id = $user_id;
            }

            $updator_name = $this->findField($model, $this->updator_name);
            if ($updator_name !== null && !in_array($updator_name, $changed, true)) {
                $model->$updator_name = $user_name;
            }
        }
    }
}