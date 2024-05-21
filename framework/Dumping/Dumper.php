<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

use JsonSerializable;
use ManaPHP\Context\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ReflectionClass;
use ReflectionNamedType;
use WeakMap;
use function class_implements;
use function is_array;
use function is_object;
use function is_string;
use function strlen;

class Dumper implements DumperInterface
{
    #[Autowired] protected ContextManagerInterface $contextManager;

    public function getProperties(object $object): array
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

            if ($property->isInitialized($object)) {
                $value = $property->getValue($object);
            } else {
                $value = null;
            }

            if ($value instanceof WeakMap) {
                $map = [];
                foreach ($value as $k => $v) {
                    $map[$k::class] = $v;
                }
                $value = $map;
            } elseif (is_object($value)) {
                continue;
            }

            $data[$name] = $value;
        }

        if ($this->contextManager->hasContext($object)) {
            $data['context'] = (array)$this->contextManager->getContext($object);
        }

        return $data;
    }

    protected function normalize(array $properties): array
    {
        foreach ($properties as $name => $value) {
            if ($value instanceof WeakMap) {
                $value = (array)$value;
            }

            if (is_string($value)) {
                if (strlen($value) > 128) {
                    $value = substr($value, 0, 128) . '...';
                }
            } elseif (is_object($value)) {
                if ($value instanceof JsonSerializable) {
                    $value = $value->jsonSerialize();
                } else {
                    $value = class_implements($value) === [] ? $value : ($value::class . '::$object');
                }
            } elseif (is_array($value)) {
                $value = $this->normalize($value);
            }
            $properties[$name] = $value;
        }
        return $properties;
    }

    public function dump(object $object): array
    {
        $properties = $this->getProperties($object);

        return $this->normalize($properties);
    }
}