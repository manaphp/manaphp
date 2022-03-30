<?php
declare(strict_types=1);

namespace ManaPHP\Di\Property;

use ManaPHP\Exception\MisuseException;
use ReflectionClass;

class Injector implements InjectorInterface
{
    protected array $types = [];

    protected function getTypes(string $class): array
    {
        $rClass = new ReflectionClass($class);
        $comment = $rClass->getDocComment();

        $types = [];
        if (is_string($comment)) {
            if (preg_match_all('#@property-read\s+\\\\?([\w\\\\]+)\s+\\$(\w+)#m', $comment, $matches, PREG_SET_ORDER)
                > 0
            ) {
                foreach ($matches as list(, $type, $name)) {
                    if ($type === 'object') {
                        continue;
                    }
                    $types[$name] = $type;
                }
            }
        }

        $parent = get_parent_class($class);
        if ($parent !== false) {
            $types += $this->types[$parent] ?? $this->getTypes($parent);
        }
        return $this->types[$class] = $types;
    }

    public function inject(string $class, string $property): string
    {
        $types = $this->types[$class] ?? $this->getTypes($class);

        if (($type = $types[$property] ?? null) === null) {
            throw new MisuseException(['can\'t type-hint for `%s`', $property]);
        }

        return $type;
    }
}