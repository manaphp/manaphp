<?php

namespace ManaPHP\Http\Client;

class File implements FileInterface
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
        $fileName = $fileName[0] === '@' ? container('alias')->resolve($fileName) : $fileName;

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
}