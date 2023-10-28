<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\Request\FileInterface;

interface RequestInterface
{
    public function getRawBody(): string;

    public function all(): array;

    public function only(array $names): array;

    public function except(array $names): array;

    public function get(string $name, mixed $default = null): mixed;

    public function set(string $name, mixed $value): static;

    public function delete(string $name): static;

    public function getServer(string $name, mixed $default = ''): mixed;

    public function getMethod(): string;

    public function has(string $name): bool;

    public function hasServer(string $name): bool;

    public function getScheme(): string;

    public function isAjax(): bool;

    public function isWebSocket(): bool;

    public function getClientIp(): string;

    public function getUserAgent(int $max_len = -1): string;

    public function isPost(): bool;

    public function isGet(): bool;

    public function isPut(): bool;

    public function isHead(): bool;

    public function isDelete(): bool;

    public function isOptions(): bool;

    public function isPatch(): bool;

    public function hasFiles(bool $onlySuccessful = true): bool;

    /**
     * Gets attached files as \ManaPHP\Http\Request\FileInterface compatible instances
     *
     * @param bool $onlySuccessful
     *
     * @return FileInterface[]
     */
    public function getFiles(bool $onlySuccessful = true): array;

    public function getFile(?string $key = null): FileInterface;

    public function hasFile(?string $key = null): bool;

    public function getReferer(int $max_len = -1): string;

    public function getOrigin(bool $strict = true): string;

    public function getHost(): string;

    public function getUrl(): string;

    public function getUri(): string;

    public function getQuery(): string;

    public function getToken(string $name = 'token'): string;

    public function getRequestId(): string;

    public function setRequestId(?string $request_id = null): string;

    public function getRequestTime(): float;

    public function getElapsedTime(int $precision = 3): float;

    public function getIfNoneMatch(): string;

    public function getAcceptLanguage(): string;
}