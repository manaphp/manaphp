<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
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

            $name = $property->getName();
            if ($property->getAttributes(Autowired::class) !== []) {
                if (($rType = $property->getType()) !== null) {
                    $type = $rType instanceof ReflectionNamedType ? $rType : $rType->getTypes()[0];
                    if (!$type->isBuiltin()) {
                        continue;
                    }
                }
            }

            if ($name === 'dependencies' && $property->getAttributes(Config::class) !== []) {
                continue;
            }

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