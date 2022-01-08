<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

interface SerializeNormalizable
{
    public function serializeNormalize(array $data): array;
}