<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use Throwable;

interface ResponseInterface
{
    public function setCookie(
        string $name,
        mixed $value,
        int $expire = 0,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httponly = true
    ): static;

    public function getCookies(): array;

    public function setStatus(int $code, ?string $text = null): static;

    public function getStatus(): string;

    public function getStatusCode(): int;

    public function getStatusText(?int $code = null): string;

    public function setHeader(string $name, string $value): static;

    public function getHeader(string $name, ?string $default = null): ?string;

    public function hasHeader(string $name): bool;

    public function removeHeader(string $name): static;

    public function setExpires(int $timestamp): static;

    public function setNotModified(): static;

    public function setETag(string $etag): static;

    public function setCacheControl(string $control): static;

    public function setMaxAge(int $age, ?string $extra = null): static;

    public function setContentType(string $contentType, ?string $charset = null): static;

    public function getContentType(): ?string;

    public function redirect(string|array $location, bool $temporarily = true): static;

    public function setContent(mixed $content): static;

    public function setJsonOk(string $message = ''): static;

    public function setJsonError(string $message, ?int $code = null): static;

    public function setJsonData(mixed $data, string $message = ''): static;

    public function setJsonThrowable(Throwable $throwable): static;

    public function setJsonContent(mixed $content): static;

    public function getContent(): mixed;

    public function hasContent(): bool;

    public function setFile(string $file, ?string $attachmentName = null): static;

    public function getFile(): ?string;

    public function hasFile(): bool;

    public function setAttachment(string $attachmentName): static;

    public function setCsvContent(array $rows, string $name, null|string|array $header = null): static;

    public function getHeaders(): array;
}