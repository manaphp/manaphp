<?php

namespace ManaPHP\Http;

interface DownloaderInterface
{
    /**
     * @param string|array           $files
     * @param string|int|array|float $options
     *
     * @return string|array
     */
    public function download($files, $options = []);
}