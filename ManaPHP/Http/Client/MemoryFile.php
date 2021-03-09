<?php

namespace ManaPHP\Http\Client;

use JsonSerializable;

class MemoryFile implements FileInterface, JsonSerializable
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $mimeType;

    /**
     * @var string
     */
    protected $postName;

    /**
     * @param string $content
     * @param string $mimeType
     * @param string $postName
     */
    public function __construct($content, $mimeType, $postName)
    {
        $this->content = $content;
        $this->mimeType = $mimeType;
        $this->postName = $postName;
    }

    /**
     * @return null
     */
    public function getFileName()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return string
     */
    public function getPostName()
    {
        return $this->postName;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return ['mimeType' => $this->mimeType, 'postName' => $this->postName];
    }
}