<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Query\QueryInterface;
use ManaPHP\Validating\ConstraintInterface;
use ManaPHP\Validating\ValidatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionAttribute;
use ReflectionProperty;

abstract class AbstractEntityManager implements EntityManagerInterface
{
    #[Autowired] protected AutoFillerInterface $autoFiller;
    #[Autowired] protected EntityMetadataInterface $entityMetadata;
    #[Autowired] protected ShardingInterface $sharding;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ValidatorInterface $validator;
    #[Autowired] protected RelationsInterface $relations;
    #[Autowired] protected MakerInterface $maker;
    #[Autowired] protected ContainerInterface $container;

    /**
     * @param Entity $entity
     * @param array  $fields
     *
     * @return void
     */
    public function validate(Entity $entity, array $fields): void
    {
        $entityClass = $entity::class;

        $validation = $this->validator->beginValidate($entity);
        foreach ($fields as $field) {
            $rProperty = new ReflectionProperty($entityClass, $field);
            $attributes = $rProperty->getAttributes(ConstraintInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes !== []) {
                $validation->field = $field;
                $validation->value = $entity->$field ?? null;

                foreach ($attributes as $attribute) {
                    /** @var ConstraintInterface $constraint */
                    $constraint = $attribute->newInstance();
                    $this->container->injectProperties($constraint);

                    if (!$validation->validate($constraint)) {
                        break;
                    }
                }

                if (!$validation->hasError($field)) {
                    $entity->$field = $validation->value;
                }
            }
        }
        $this->validator->endValidate($validation);
    }

    public function with(string $entityClass, array $withs): static
    {
        $this->relations->earlyLoad($entityClass, [$this], $withs);
        return $this;
    }

    public function relations(): array
    {
        return [];
    }

    /**
     * @param string  $entityClass
     * @param ?string $alias
     *
     * @return QueryInterface <static>
     */
    public function query(string $entityClass): QueryInterface
    {
        return $this->newQuery()->from($entityClass);
    }

    abstract protected function newQuery(): QueryInterface;
}