<?php
declare(strict_types=1);

namespace ManaPHP\Di\Property;

use ManaPHP\Exception\MisuseException;
use ReflectionClass;

class Injector implements InjectorInterface
{
    protected array $types = [];

    protected function getUses(ReflectionClass $rClass): array
    {
        $short = $rClass->getShortName();
        $file = $rClass->getFileName();
        if (!is_string($file) || !str_ends_with($file, "$short.php")) {
            return [];
        }

        $str = file_get_contents($file);

        preg_match_all('#^use\s+(.+);#m', $str, $matches);

        $types = [];
        foreach ($matches[1] as $use) {
            $use = trim($use);

            if (str_contains($use, ' as ')) {
                list($full, , $short) = preg_split('#\s+#', $use, -1, PREG_SPLIT_NO_EMPTY);
                $types[$short] = $full;
            } else {
                if (($pos = strrpos($use, '\\')) !== false) {
                    $types[substr($use, $pos + 1)] = $use;
                } else {
                    $types[$use] = $use;
                }
            }
        }

        return $types;
    }

    protected function getTypes(string $class): array
    {
        $rClass = new ReflectionClass($class);
        $comment = $rClass->getDocComment();

        $uses = null;
        $types = [];
        if (is_string($comment)) {
            if (preg_match_all('#@property-read\s+([\w\\\\]+)\s+\\$(\w+)#m', $comment, $matches, PREG_SET_ORDER)
                > 0
            ) {
                foreach ($matches as list(, $type, $name)) {
                    if ($type === '\object') {
                        continue;
                    }

                    if ($type[0] === '\\') {
                        $types[$name] = substr($type, 1);
                    } else {
                        if ($uses === null) {
                            $uses = $this->getUses($rClass);
                        }

                        if (isset($uses[$type])) {
                            $types[$name] = $uses[$type];
                        } else {
                            $types[$name] = $rClass->getNamespaceName() . '\\' . $type;
                        }
                    }
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
            throw new MisuseException(['can\'t type-hint for `%s::%s`', $class, $property]);
        }

        return $type;
    }
}