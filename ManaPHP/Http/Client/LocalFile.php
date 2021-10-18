<?php

namespace ManaPHP\Http\Client;

use JsonSerializable;
use ManaPHP\AliasInterface;

class LocalFile implements FileInterface, JsonSerializable
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $mimeType;

    /**
     * @var string
     */
    protected $postName;

    /**
     * @param string $fileName
     * @param string $mimeType
     * @param string $postName
     */
    public function __construct($fileName, $mimeType = null, $postName = null)
    {
        $fileName = $fileName[0] === '@' ? container(AliasInterface::class)->resolve($fileName) : $fileName;

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

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType ?? mime_content_type($this->fileName);
    }

    /**
     * @return string
     */
    public function getPostName()
    {
        return $this->postName ?? basename($this->fileName);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return ['fileName' => $this->fileName];
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return file_get_contents($this->fileName);
    }
}