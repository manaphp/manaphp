<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Model\ModelsInterface;
use ManaPHP\Validating\Constraint\Attribute\Type;
use ManaPHP\Validating\ValidatorInterface;
use ReflectionAttribute;
use ReflectionProperty;

class EntityFiller implements EntityFillerInterface
{
    #[Autowired] protected ModelsInterface $models;
    #[Autowired] protected ValidatorInterface $validator;

    public function fill(object $entity, array $data): object
    {
        $validation = $this->validator->beginValidate($data);
        foreach ($this->models->getFillable($entity::class) as $field) {
            if (($value = $data[$field] ?? null) !== null) {
                $validation->field = $field;
                $validation->value = $value;

                $rProperty = new ReflectionProperty($entity::class, $field);

                if ($attributes = $rProperty->getAttributes(Type::class, ReflectionAttribute::IS_INSTANCEOF)) {
                    $constraint = $attributes[0]->newInstance();
                } else {
                    $constraint = new Type($rProperty->getType()?->getName() ?? 'mixed');
                }

                if ($validation->validate($constraint)) {
                    $entity->$field = $validation->value;
                }
            }
        }
        $this->validator->endValidate($validation);

        return $entity;
    }
}