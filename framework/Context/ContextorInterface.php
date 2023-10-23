<?php
declare(strict_types=1);

namespace ManaPHP\Context;

interface ContextorInterface
{
    public function findContext(object $object): ?string;

    public function makeContext(object $object);

    public function createContext(object $object): object;

    public function getContext(object $object, int $cid = 0): object;

    public function hasContext(object $object): bool;

    public function resetContexts(): void;
}