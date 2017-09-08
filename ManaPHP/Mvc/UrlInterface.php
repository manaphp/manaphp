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
     * @param string|array $args
     * @param string       $module
     *
     * @return string
     */
    public function get($args, $module = null);

    /**
     * @param string $uri
     *
     * @return string
     */
    public function getAsset($uri);
}