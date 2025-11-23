<?php

declare(strict_types=1);

namespace ManaPHP\Http\Request;

use JsonSerializable;
use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\Request\File\Exception as FileException;
use function dirname;
use function error_get_last;
use function is_uploaded_file;
use function pathinfo;
use function unlink;

class File implements FileInterface, JsonSerializable
{
    #[Autowired] protected array $file;

    public function getSize(): int
    {
        return $this->file['size'];
    }

    public function getName(): string
    {
        return $this->file['name'];
    }

    public function getTempName(): string
    {
        return $this->file['tmp_name'];
    }

    public function getType(bool $real = true): string
    {
        if ($real) {
            return mime_content_type($this->file['tmp_name']) ?: '';
        } else {
            return $this->file['type'];
        }
    }

    public function getError(): string
    {
        return $this->file['error'];
    }

    public function getKey(): string
    {
        return $this->file['key'];
    }

    public function isUploadedFile(): bool
    {
        return is_uploaded_file($this->file['tmp_name']);
    }

    public function moveTo(
        string $dst,
        string $allowedExtensions = 'jpg,jpeg,png,gif,doc,xls,pdf,zip',
        bool   $overwrite = false
    ): void
    {
        if ($allowedExtensions !== '*') {
            $extension = pathinfo($dst, PATHINFO_EXTENSION);
            if (!$extension || preg_match("#\b$extension\b#", $allowedExtensions) !== 1) {
                throw new FileException('The file type "{extension}" is not allowed for upload.', ['extension' => $extension]);
            }
        }

        if (($error = $this->file['error']) !== UPLOAD_ERR_OK) {
            throw new FileException('File upload failed with error code {error}.', ['error' => $error]);
        }

        if (LocalFS::fileExists($dst)) {
            if ($overwrite) {
                LocalFS::fileDelete($dst);
            } else {
                throw new FileException('The file "{dst}" already exists.', ['dst' => $dst]);
            }
        }

        LocalFS::dirCreate(dirname($dst));

        if (PHP_SAPI === 'cli') {
            LocalFS::fileMove($this->file['tmp_name'], Path::of($dst));
        } elseif (!move_uploaded_file($this->file['tmp_name'], Path::resolve($dst))) {
            $error = error_get_last()['message'] ?? '';
            throw new FileException('Could not move uploaded file to destination "{dst}": {error}.', ['dst' => $dst, 'error' => $error]);
        }

        if (!chmod(Path::resolve($dst), 0644)) {
            $error = error_get_last()['message'] ?? '';
            throw new FileException('Could not set file permissions for destination "{dst}": {error}.', ['dst' => $dst, 'error' => $error]);
        }
    }

    public function getExtension(): string
    {
        $name = $this->file['name'];
        return ($extension = pathinfo($name, PATHINFO_EXTENSION)) === $name ? '' : $extension;
    }

    public function delete(): void
    {
        @unlink($this->file['tmp_name']);
    }

    public function jsonSerialize(): array
    {
        return $this->file;
    }
}
