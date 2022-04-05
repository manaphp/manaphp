<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Primary;

#[Primary('ManaPHP\Http\Session\Adapter\Redis')]
interface SessionInterface
{
    public function get(?string $name = null, mixed $default = null): mixed;

    public function set(string $name, mixed $value): static;

    public function has(string $name): bool;

    public function remove(string $name): static;

    public function destroy(?string $session_id = null): static;

    public function getId(): string;

    public function setId(string $id): static;

    public function getName(): string;

    public function getTtl(): int;

    public function setTtl(int $ttl): static;

    public function read(string $session_id): array;

    public function write(string $session_id, array $data): static;
}