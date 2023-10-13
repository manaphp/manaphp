<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

class MemoryFile implements FileInterface, JsonSerializable
{
    protected string $content;
    protected string $mimeType;
    protected string $postName;

    public function __construct(string $content, string $mimeType, string $postName)
    {
        $this->content = $content;
        $this->mimeType = $mimeType;
        $this->postName = $postName;
    }

    public function getFileName(): ?string
    {
        return null;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getPostName(): string
    {
        return $this->postName;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    #[ArrayShape(['mimeType' => 'string', 'postName' => 'string'])]
    public function jsonSerialize(): array
    {
        return ['mimeType' => $this->mimeType, 'postName' => $this->postName];
    }
}