<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

interface SqlFragmentable
{
    public function setField(string $name): static;

    public function getSql(): string;

    public function getBind(): array;
}