<?php
declare(strict_types=1);

namespace ManaPHP\Model;

interface SerializeNormalizable
{
    public function serializeNormalize(array $data): array;
}