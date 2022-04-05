<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface ContainerInterface
{
    public function set(string $id, mixed $definition): static;

    public function getDefinitions(): array;

    public function getDefinition(string $id): mixed;

    public function getInstances(): array;

    public function remove(string $id): static;

    public function make(string $name, array $parameters = []): mixed;

    public function get(string $id): mixed;

    public function inject(object $target, string $property): mixed;

    public function has(string $id): bool;

    public function call(callable $callable, array $parameters = []): mixed;
}