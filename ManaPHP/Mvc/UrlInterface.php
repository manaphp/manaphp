<?php
namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\UrlInterface
 *
 * @package url
 */
interface UrlInterface
{
    /**
     * @param string|array $uri
     * @param array        $args
     * @param string       $module
     *
     * @return string
     */
    public function get($uri, $args = [], $module = null);

    /**
     * @param string $uri
     *
     * @return string
     */
    public function getAsset($uri);
}