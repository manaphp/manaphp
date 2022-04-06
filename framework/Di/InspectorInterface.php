<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface InspectorInterface
{
    public function getDefinitions(): array;

    public function getDefinition(string $id): mixed;

    public function getInstances(): array;
}