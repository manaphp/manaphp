<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

interface FileInterface
{
    public function getFileName(): ?string;

    public function getMimeType(): string;

    public function getPostName(): string;

    public function getContent(): string;
}