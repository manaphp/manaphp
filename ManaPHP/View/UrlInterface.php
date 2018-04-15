<?php
namespace ManaPHP\View;

/**
 * Interface ManaPHP\View\UrlInterface
 *
 * @package url
 */
interface UrlInterface
{
    /**
     * @param string|array $args
     *
     * @return string
     */
    public function get($args);

    /**
     * @param string $path
     *
     * @return string
     */
    public function getAsset($path);
}