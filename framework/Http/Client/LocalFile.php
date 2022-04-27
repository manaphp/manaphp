<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use ManaPHP\AliasInterface;
use ManaPHP\Helper\Container;

class LocalFile implements FileInterface, JsonSerializable
{
    protected string $fileName;
    protected ?string $mimeType;
    protected string $postName;

    public function __construct(string $fileName, ?string $mimeType = null, ?string $postName = null)
    {
        $fileName = $fileName[0] === '@' ? Container::get(AliasInterface::class)->resolve($fileName) : $fileName;

        if (!file_exists($fileName)) {
            throw new Exception(["`%s` is not exist", $fileName]);
        }

        if (!is_readable($fileName)) {
            throw new Exception(["`%s` is not readable", $fileName]);
        }

        $this->fileName = $fileName;
        $this->mimeType = $mimeType;
        $this->postName = $postName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType ?? mime_content_type($this->fileName);
    }

    public function getPostName(): string
    {
        return $this->postName ?? basename($this->fileName);
    }

    #[ArrayShape(['fileName' => "mixed"])]
    public function jsonSerialize(): array
    {
        return ['fileName' => $this->fileName];
    }

    public function getContent(): string
    {
        return file_get_contents($this->fileName);
    }
}