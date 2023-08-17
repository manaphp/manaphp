<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Inject;

class Dumper implements DumperInterface
{
    #[Inject] protected ContextorInterface $contextor;

    public function dump(object $object): array
    {
        $data = [];
        $rf = new \ReflectionClass($object);
        foreach ($rf->getProperties() as $property) {
            if ($property->isStatic() || $property->getAttributes(Inject::class) !== []) {
                continue;
            }

            $name = $property->getName();
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