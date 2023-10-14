<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ReflectionClass;
use ReflectionNamedType;

class Dumper implements DumperInterface
{
    #[Autowired] protected ContextorInterface $contextor;

    public function dump(object $object): array
    {
        $data = [];
        $rf = new ReflectionClass($object);
        foreach ($rf->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getAttributes(Autowired::class) !== []) {
                if (($rType = $property->getType()) !== null) {
                    $type = $rType instanceof ReflectionNamedType ? $rType : $rType->getTypes()[0];
                    if (!$type->isBuiltin()) {
                        continue;
                    }
                }
            }

            $name = $property->getName();
            $property->setAccessible(true);
            if ($property->isInitialized($object)) {
                $value = $property->getValue($object);
            } else {
                $value = null;
            }

            if (is_object($value)) {
                continue;
            }

            $data[$name] = $value;
        }

        if ($this->contextor->hasContext($object)) {
            $data['context'] = (array)$this->contextor->getContext($object);
        }

        return $data;
    }
}