<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface GlobalsInterface
{
    public function prepare(array $GET, array $POST, array $SERVER, ?string $RAW_BODY = null, array $COOKIE = [],
        array $FILES = []
    ): void;

    public function get(): GlobalsContext;

    public function getServer(): array;

    public function setServer(string $name, mixed $value): static;

    public function getFiles(): array;

    public function getRequest(): array;

    public function getRawBody(): ?string;

    public function getCookie(): array;

    public function unsetCookie(string $name): static;

    public function setCookie(string $name, string $value): static;
}