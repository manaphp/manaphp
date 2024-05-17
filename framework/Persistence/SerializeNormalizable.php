<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface SerializeNormalizable
{
    public function serializeNormalize(array $data): array;
}