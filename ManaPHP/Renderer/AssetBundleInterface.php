<?php

namespace ManaPHP\Renderer;

interface AssetBundleInterface
{
    /**
     * @param array  $files
     * @param string $name
     *
     * @return string
     */
    public function bundle($files, $name = 'app');
}