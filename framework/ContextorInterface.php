<?php
declare(strict_types=1);

namespace ManaPHP;

interface ContextorInterface
{
    public function findContext(object $object): ?string;

    public function makeContext(object $object);

    public function createContext(object $object): object;

    public function getContext(object $object): object;

    public function hasContext(object $object): bool;

    public function resetContexts(): void;
}