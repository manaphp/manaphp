<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface AutoFillerInterface
{
    public function fillCreated(Entity $entity): void;

    public function fillUpdated(Entity $entity): void;
}