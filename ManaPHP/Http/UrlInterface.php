<?php

namespace ManaPHP\Http;

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