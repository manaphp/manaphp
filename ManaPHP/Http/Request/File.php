<?php

namespace ManaPHP\Http\Request;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\Request\File\Exception as FileException;

class File extends Component implements FileInterface
{
    /**
     * @var array
     */
    protected $file;

    /**
     * @param array $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Returns the file size of the uploaded file
     *
     * @return int
     */
    public function getSize()
    {
        return $this->file['size'];
    }

    /**
     * Returns the real name of the uploaded file
     *
     * @return string
     */
    public function getName()
    {
        return $this->file['name'];
    }

    /**
     * Returns the temporary name of the uploaded file
     *
     * @return string
     */
    public function getTempName()
    {
        return $this->file['tmp_name'];
    }

    /**
     * @param bool $real
     *
     * @return string
     */
    public function getType($real = true)
    {
        if ($real) {
            return mime_content_type($this->file['tmp_name']) ?: '';
        } else {
            return $this->file['type'];
        }
    }

    /**
     * Returns the error code
     *
     * @return string
     */
    public function getError()
    {
        return $this->file['error'];
    }

    /**
     * Returns the file key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->file['key'];
    }

    /**
     * Checks whether the file has been uploaded via Post.
     *
     * @return bool
     */
    public function isUploadedFile()
    {
        return is_uploaded_file($this->file['tmp_name']);
    }

    /**
     * Moves the temporary file to a destination within the application
     *
     * @param string $dst
     * @param string $allowedExtensions
     * @param bool   $overwrite
     *
     * @return void
     * @throws \ManaPHP\Http\Request\File\Exception
     *
     */
    public function moveTo($dst, $allowedExtensions = 'jpg,jpeg,png,gif,doc,xls,pdf,zip', $overwrite = false)
    {
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

    /**
     * Returns the file extension
     *
     * @return string
     */
    public function getExtension()
    {
        $name = $this->file['name'];
        return ($extension = pathinfo($name, PATHINFO_EXTENSION)) === $name ? '' : $extension;
    }

    /**
     * @return void
     */
    public function delete()
    {
        @unlink($this->file['tmp_name']);
    }

    public function jsonSerialize()
    {
        return $this->file;
    }
}