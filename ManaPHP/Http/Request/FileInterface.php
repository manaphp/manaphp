<?php
declare(strict_types=1);

namespace ManaPHP\Http\Request;

interface FileInterface
{
    public function getKey(): string;

    public function getSize(): int;

    public function getName(): string;

    public function getTempName(): string;

    public function getType(bool $real = true): string;

    public function moveTo(string $dst, string $allowedExtensions = 'jpg,jpeg,png,gif,doc,xls,pdf,zip',
        bool $overwrite = false
    ): void;

    public function getExtension(): string;

    public function delete(): void;
}