<?php

namespace ManaPHP\Http\Client;

interface FileInterface
{
    /**
     * @return string
     */
    public function getFileName();

    /**
     * @return string
     */
    public function getMimeType();

    /**
     * @return string
     */
    public function getPostName();
}