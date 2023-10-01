<?php
declare(strict_types=1);

namespace ManaPHP\Identifying;

interface IdentityInterface
{
    public function isGuest(): bool;

    public function getId(): int;

    public function getName(): string;

    public function getRoles(): array;

    public function set(array $claims): void;
}