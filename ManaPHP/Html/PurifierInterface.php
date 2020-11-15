<?php

namespace ManaPHP\Html;

interface PurifierInterface
{
    /**
     * @param string $html
     * @param array  $allowedTags
     * @param array  $allowedAttributes
     *
     * @return string
     */
    public function purify($html, $allowedTags = null, $allowedAttributes = null);
}