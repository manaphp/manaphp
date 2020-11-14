<?php

namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\UrlInterface
 *
 * @package url
 */
interface UrlInterface
{
    /**
     * @param string|array $args
     * @param string|bool  $scheme
     *
     * @return string
     */
    public function get($args, $scheme = false);
}