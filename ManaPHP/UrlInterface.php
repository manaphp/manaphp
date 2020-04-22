<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\UrlInterface
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