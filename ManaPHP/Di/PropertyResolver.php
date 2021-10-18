<?php

namespace ManaPHP\Di;

use ReflectionClass;
use InvalidArgumentException;

class PropertyResolver
{
    /**
     * @var array
     */
    protected $resolved = [];

    /**
     * @param string $class
     * @param string $property
     *
     * @return string
     */
    public function resolve($class, $property)
    {
        $resolved = $this->resolved[$class] ?? $this->resolveInternal($class);

        if (($type = $resolved[$property] ?? null) === null) {
            throw new InvalidArgumentException('sss');
        }

        return $type;
    }

    /**
     * @param string $class
     *
     * @return array
     */
    protected function resolveInternal($class)
    {
        $rc = new ReflectionClass($class);
        $comment = $rc->getDocComment();

        $resolved = [];
        if (is_string($comment)) {
            if (preg_match_all('#@property-read\s+\\\\?([\w\\\\]+)\s+\\$(\w+)#m', $comment, $matches, PREG_SET_ORDER)
                > 0
            ) {
                foreach ($matches as $match) {
                    $resolved[$match[2]] = $match[1];
                }
            }
        }

        $parent = get_parent_class($class);
        if ($parent !== false) {
            $resolved += $this->resolved[$parent] ?? $this->resolveInternal($parent);
        }
        return $this->resolved[$class] = $resolved;
    }
}