<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Container\ContainerInterface;

class DumperManager implements DumperManagerInterface
{
    #[Autowired] protected ContainerInterface $container;

    #[Autowired] protected array $dumpers = [];

    protected array $instances = [];

    protected function getDumperClass(object $object): string
    {
        $class = $object::class;

        if (($dumper = $this->dumpers[$class] ?? null) !== null) {
            return $dumper;
        }

        foreach (class_implements($object) ?? [] as $interface) {
            if (!str_contains($interface, '\\')) {
                continue;
            }
            $dumper = substr($interface, 0, -9) . 'Dumper';
            if (class_exists($dumper)) {
                return $dumper;
            }
        }

        $dumper = $class . 'Dumper';
        if (class_exists($dumper)) {
            return $dumper;
        }

        return DumperInterface::class;
    }

    public function dump(object $object): array
    {
        if ($object instanceof DumperInterface) {
            return [];
        } elseif ($object === $this) {
            return [];
        }

        $class = $object::class;

        /** @var DumperInterface $dumper */
        if (($dumper = $this->instances[$class] ?? null) !== null) {
            return $dumper->dump($object);
        }

        $dumperClass = $this->getDumperClass($object);
        $dumper = $this->instances[$object::class] = $this->container->get($dumperClass);

        return $dumper->dump($object);
    }
}