<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface EntityFillerInterface
{
    public function fill(object $entity, array $data);
}