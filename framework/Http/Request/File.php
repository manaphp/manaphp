<?php
declare(strict_types=1);

namespace ManaPHP\Http\Request;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\Request\File\Exception as FileException;

class File implements FileInterface
{
    #[Inject] protected AliasInterface $alias;

    #[Value] protected array $file;

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

    public function moveTo(string $dst, string $allowedExtensions = 'jpg,jpeg,png,gif,doc,xls,pdf,zip',
        bool $overwrite = false
    ): void {
        if ($allowedExtensions !== '*') {
            $extension = pathinfo($dst, PATHINFO_EXTENSION);
            if (!$extension || preg_match("#\b$extension\b#", $allowedExtensions) !== 1) {
                throw new FileException(['`:extension` file type is not allowed upload', 'extension' => $extension]);
            }
        }

        if (($error = $this->file['error']) !== UPLOAD_ERR_OK) {
            throw new FileException(['error code of upload file is not UPLOAD_ERR_OK: :error', 'error' => $error]);
        }

        if (LocalFS::fileExists($dst)) {
            if ($overwrite) {
                LocalFS::fileDelete($dst);
            } else {
                throw new FileException(['`:file` file already exists', 'file' => $dst]);
            }
        }

        LocalFS::dirCreate(dirname($dst));

        if (PHP_SAPI === 'cli') {
            LocalFS::fileMove($this->file['tmp_name'], $this->alias->resolve($dst));
        } else {
            if (!move_uploaded_file($this->file['tmp_name'], $this->alias->resolve($dst))) {
                $error = error_get_last()['message'] ?? '';
                throw new FileException(['move_uploaded_file to `%s` failed: %s', $dst, $error]);
            }
        }

        if (!chmod($this->alias->resolve($dst), 0644)) {
            $error = error_get_last()['message'] ?? '';
            throw new FileException(['chmod `%s` destination failed: %s', $dst, $error]);
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