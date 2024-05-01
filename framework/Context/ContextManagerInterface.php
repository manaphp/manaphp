<?php
declare(strict_types=1);

namespace ManaPHP\Context;

interface ContextManagerInterface
{
    public function findContext(object $object): ?string;

    public function makeContext(object $object);

    public function createContext(object $object): object;

    public function getContext(object $object, int $cid = 0): mixed;

    public function hasContext(object $object): bool;

    public function resetContexts(): void;
}