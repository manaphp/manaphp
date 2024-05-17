<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface EntityFillerInterface
{
    public function fill(Entity $entity, array $data);
}